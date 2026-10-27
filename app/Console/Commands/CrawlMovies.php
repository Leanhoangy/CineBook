<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Cào danh sách phim đang chiếu / sắp chiếu tại Việt Nam từ Moveek.com.
 * Kết quả lưu vào database/seeders/data (movies.json + thư mục posters) để MovieSeeder dùng lại khi seed,
 * nhờ vậy chạy migrate --seed không cần Internet.
 */
#[Signature('movies:crawl
    {--upcoming=20 : Số phim sắp chiếu tối đa}
    {--ended=15 : Số phim đã ngừng chiếu (lấy từ sitemap, ưu tiên thể loại còn thiếu)}
    {--scan=150 : Số trang phim tối đa được quét để tìm phim đã ngừng chiếu}
    {--delay=2000 : Thời gian nghỉ giữa 2 request (ms), không nên giảm để tránh bị Moveek chặn}')]
#[Description('Cào dữ liệu phim thật từ Moveek.com vào database/seeders/data')]
class CrawlMovies extends Command
{
    private const BASE_URL = 'https://moveek.com';

    /** Thể loại tiếng Anh trên Moveek => thể loại trong CineBook */
    private const GENRES = [
        'Action' => 'Hành động',
        'Adventure' => 'Phiêu lưu',
        'Animation' => 'Hoạt hình',
        'Comedy' => 'Hài',
        'Crime' => 'Tội phạm',
        'Documentary' => 'Tài liệu',
        'Drama' => 'Tâm lý',
        'Family' => 'Gia đình',
        'Fantasy' => 'Giả tưởng',
        'History' => 'Lịch sử',
        'Horror' => 'Kinh dị',
        'Music' => 'Âm nhạc',
        'Musical' => 'Âm nhạc',
        'Mystery' => 'Bí ẩn',
        'Romance' => 'Tình cảm',
        'Science Fiction' => 'Khoa học viễn tưởng',
        'Sci-Fi' => 'Khoa học viễn tưởng',
        'Thriller' => 'Hồi hộp',
        'War' => 'Chiến tranh',
    ];

    /** Moveek trả về 403/429 khi bị gửi quá nhiều request: dừng ngay, không gửi tiếp */
    private bool $blocked = false;

    public function handle(): int
    {
        $dataDir = database_path('seeders/data');
        $posterDir = $dataDir.'/posters';
        File::ensureDirectoryExists($posterDir);

        $nowShowing = $this->listSlugs('/dang-chieu/');
        $upcoming = array_slice(array_diff($this->listSlugs('/sap-chieu/'), $nowShowing), 0, (int) $this->option('upcoming'));

        $queue = array_merge(
            array_fill_keys($nowShowing, 'now_showing'),
            array_fill_keys($upcoming, 'coming_soon'),
        );

        if (! $queue) {
            $this->error('Không lấy được danh sách phim. Kiểm tra kết nối mạng hoặc cấu trúc trang Moveek.');

            return self::FAILURE;
        }

        $this->info('Tìm thấy '.count($nowShowing).' phim đang chiếu, '.count($upcoming).' phim sắp chiếu.');

        $movies = [];
        $bar = $this->output->createProgressBar(count($queue));

        foreach ($queue as $slug => $status) {
            if ($movie = $this->tryParse($slug, $status)) {
                $movies[] = $movie;
            }
            $bar->advance();

            if ($this->blocked) {
                $this->newLine();
                $this->error('Moveek đang tạm chặn do gửi quá nhiều request. Dữ liệu cũ được giữ nguyên, hãy thử lại sau vài giờ.');

                return self::FAILURE;
            }
        }

        $bar->finish();
        $this->newLine();

        if ((int) $this->option('ended') > 0) {
            $movies = array_merge($movies, $this->crawlEnded(array_keys($queue), $movies));
        }

        $this->info('Đang tải poster...');
        foreach ($movies as $i => $movie) {
            $movies[$i]['poster'] = $this->downloadPoster($movie, $posterDir);
            unset($movies[$i]['poster_url']);
        }

        // Xóa poster của những phim không còn trong danh sách
        $keep = array_column($movies, 'poster');
        foreach (File::files($posterDir) as $file) {
            if (! in_array($file->getFilename(), $keep, true)) {
                File::delete($file->getPathname());
            }
        }

        File::put($dataDir.'/movies.json', json_encode([
            'source' => self::BASE_URL,
            'crawled_at' => now()->toIso8601String(),
            'movies' => $movies,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info('Đã lưu '.count($movies).' phim vào database/seeders/data/movies.json');
        $this->line('Chạy <comment>php artisan migrate:fresh --seed</comment> để nạp dữ liệu mới vào database.');

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function listSlugs(string $path): array
    {
        $html = $this->fetch($path);

        // Chỉ lấy các thẻ phim trong lưới chính, bỏ qua link ở sidebar/menu
        preg_match_all('~<h3 class="text-truncate h4 mb-1">\s*<a href="/phim/([^"/]+)/"~', $html, $m);

        return array_values(array_unique($m[1]));
    }

    /**
     * Tìm phim đã ngừng chiếu: duyệt sitemap từ phim mới thêm nhất trở về trước,
     * lấy phim khởi chiếu trong vòng 1 năm, ưu tiên phim có thể loại chưa có trong dữ liệu.
     */
    private function crawlEnded(array $exclude, array $current): array
    {
        $limit = (int) $this->option('ended');
        $xml = $this->fetch('/sitemap-movies.xml');
        preg_match_all('~<loc>https://moveek\.com/phim/([^/<]+)/</loc>~', $xml, $m);
        $slugs = array_slice(array_values(array_diff(array_reverse(array_unique($m[1])), $exclude)), 0, (int) $this->option('scan'));

        $this->info('Quét '.count($slugs).' phim gần đây để tìm phim đã ngừng chiếu...');
        $bar = $this->output->createProgressBar(count($slugs));
        $candidates = [];

        foreach ($slugs as $slug) {
            $movie = $this->tryParse($slug, 'ended');
            $bar->advance();

            if ($this->blocked) {
                $this->newLine();
                $this->warn('Moveek đang tạm chặn, dừng quét và dùng các phim đã tìm được.');
                break;
            }

            $released = $movie['release_date'] ?? null;
            if ($movie && $movie['poster_url'] && $movie['genres'] && $movie['duration']
                && $released >= now()->subYear()->toDateString()
                && $released <= now()->subWeeks(3)->toDateString()) {
                $candidates[] = $movie;
            }
        }

        $bar->finish();
        $this->newLine();

        // Lần lượt chọn phim bổ sung được thể loại còn thiếu, sau đó lấp đầy bằng phim mới nhất
        $covered = array_merge(...array_column($current, 'genres'));
        $selected = [];

        foreach ($candidates as $i => $movie) {
            if (count($selected) < $limit && array_diff($movie['genres'], $covered)) {
                $selected[$i] = $movie;
                $covered = array_merge($covered, $movie['genres']);
            }
        }
        foreach ($candidates as $i => $movie) {
            if (count($selected) < $limit && ! isset($selected[$i])) {
                $selected[$i] = $movie;
            }
        }

        $missing = array_diff(array_unique(self::GENRES), $covered);
        $this->info('Chọn '.count($selected).' phim đã ngừng chiếu.'.($missing ? ' Thể loại vẫn chưa có phim: '.implode(', ', $missing) : ''));

        return array_values($selected);
    }

    private function tryParse(string $slug, string $status): ?array
    {
        try {
            return $this->parseMovie($slug, $status);
        } catch (RequestException $e) {
            $this->blocked = in_array($e->response->status(), [403, 429], true);
            $this->newLine();
            $this->warn("Bỏ qua {$slug}: HTTP {$e->response->status()}");

            return null;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->warn("Bỏ qua {$slug}: {$e->getMessage()}");

            return null;
        } finally {
            usleep((int) $this->option('delay') * 1000);
        }
    }

    private function downloadPoster(array $movie, string $posterDir): ?string
    {
        if (! $url = $movie['poster_url']) {
            return null;
        }

        $slug = basename($movie['source_url']);
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
        $file = "{$slug}.{$ext}";

        if (! is_file("{$posterDir}/{$file}")) {
            File::put("{$posterDir}/{$file}", $this->http()->get($url)->throw()->body());
        }

        return $file;
    }

    private function parseMovie(string $slug, string $status): array
    {
        $html = $this->fetch("/phim/{$slug}/");
        $data = $this->movieJsonLd($html);

        if (! $data || empty($data['name'])) {
            throw new \RuntimeException('trang không có dữ liệu JSON-LD');
        }

        preg_match('~data-video-url="([\w-]{11})"~', $html, $trailer);

        return [
            'source_url' => self::BASE_URL."/phim/{$slug}/",
            'title' => html_entity_decode(trim($data['name']), ENT_QUOTES),
            'description' => trim($data['description'] ?? '') ?: 'Nội dung phim đang được cập nhật.',
            'director' => $this->names($data['director'] ?? [], 150),
            'cast' => $this->names($data['actor'] ?? [], 255),
            'duration' => $this->minutes($data['duration'] ?? null),
            'release_date' => isset($data['datePublished'])
                ? Carbon::parse($data['datePublished'])->toDateString()
                : null,
            'age_rating' => $this->ageRating($data['contentRating'] ?? null),
            'status' => $status,
            'genres' => $this->genres($data['genre'] ?? ''),
            'trailer_url' => isset($trailer[1]) ? 'https://www.youtube.com/watch?v='.$trailer[1] : null,
            'poster_url' => Arr::get($data, 'image.url'),
        ];
    }

    private function movieJsonLd(string $html): ?array
    {
        preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m);

        foreach ($m[1] as $json) {
            $data = json_decode(trim($json), true);
            if (($data['@type'] ?? null) === 'Movie') {
                return $data;
            }
        }

        return null;
    }

    /** Ghép tên người, cắt bớt cho vừa độ dài cột trong database */
    private function names(array $people, int $max): ?string
    {
        $result = '';

        foreach (array_filter(array_column($people, 'name')) as $name) {
            $next = $result === '' ? $name : "{$result}, {$name}";
            if (mb_strlen($next) > $max) {
                break;
            }
            $result = $next;
        }

        return $result ?: null;
    }

    /** "PT2H13M" / "PT133M" => 133 */
    private function minutes(?string $iso): ?int
    {
        if (! $iso || ! preg_match('~PT(?:(\d+)H)?(?:(\d+)M)?~', $iso, $m)) {
            return null;
        }

        $minutes = (int) ($m[1] ?? 0) * 60 + (int) ($m[2] ?? 0);

        return $minutes ?: null;
    }

    /** Việt Nam phân loại P, K, T13, T16, T18 (C = cấm chiếu); CineBook gộp K vào P */
    private function ageRating(?string $rating): string
    {
        $rating = strtoupper(trim((string) $rating));

        return in_array($rating, ['T13', 'T16', 'T18'], true) ? $rating : 'P';
    }

    /** @return list<string> */
    private function genres(string $genres): array
    {
        $result = [];

        foreach (array_map('trim', explode(',', $genres)) as $genre) {
            if ($genre === '') {
                continue;
            }
            if (isset(self::GENRES[$genre])) {
                $result[] = self::GENRES[$genre];
            } elseif (in_array($genre, self::GENRES, true)) {
                $result[] = $genre;
            } else {
                $this->newLine();
                $this->warn("Thể loại chưa ánh xạ: {$genre}");
            }
        }

        return array_values(array_unique($result));
    }

    private function fetch(string $path): string
    {
        return $this->http()->get(self::BASE_URL.$path)->throw()->body();
    }

    private function http()
    {
        return Http::withUserAgent('Mozilla/5.0 (compatible; CineBookCrawler/1.0; student project)')
            ->timeout(20)
            // Chỉ thử lại khi lỗi mạng/lỗi máy chủ; bị chặn (403/429) thì thử lại chỉ làm tình hình tệ hơn
            ->retry(3, 2000, fn ($e) => ! $e instanceof RequestException || $e->response->serverError());
    }
}
