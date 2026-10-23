<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Cào danh sách phim đang chiếu / sắp chiếu tại Việt Nam từ Moveek.com.
 * Kết quả lưu vào database/seeders/data (movies.json + thư mục posters) để MovieSeeder dùng lại khi seed,
 * nhờ vậy chạy migrate --seed không cần Internet.
 */
#[Signature('movies:crawl {--upcoming=20 : Số phim sắp chiếu tối đa} {--delay=1000 : Thời gian nghỉ giữa 2 request (ms)}')]
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
            try {
                $movie = $this->crawlMovie($slug, $status, $posterDir);
                if ($movie) {
                    $movies[] = $movie;
                }
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("Bỏ qua {$slug}: {$e->getMessage()}");
            }

            $bar->advance();
            usleep((int) $this->option('delay') * 1000);
        }

        $bar->finish();
        $this->newLine();

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

    private function crawlMovie(string $slug, string $status, string $posterDir): ?array
    {
        $html = $this->fetch("/phim/{$slug}/");
        $data = $this->movieJsonLd($html);

        if (! $data || empty($data['name'])) {
            throw new \RuntimeException('trang không có dữ liệu JSON-LD');
        }

        $poster = null;
        if ($url = Arr::get($data, 'image.url')) {
            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
            $poster = "{$slug}.{$ext}";
            File::put("{$posterDir}/{$poster}", $this->http()->get($url)->throw()->body());
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
            'poster' => $poster,
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
            ->retry(3, 2000);
    }
}
