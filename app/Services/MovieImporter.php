<?php

namespace App\Services;

use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Nạp phim từ database/seeders/data/movies.json (tạo bởi lệnh movies:crawl) vào database.
 * Phim chưa có thì thêm mới, phim đã có (trùng tên) thì cập nhật trạng thái theo dữ liệu mới nhất,
 * nên chạy lại nhiều lần không bị trùng và không mất dữ liệu đặt vé.
 */
class MovieImporter
{
    public const DATA_FILE = 'seeders/data/movies.json';

    /** Phim sắp chiếu chưa công bố thời lượng thì tạm tính 120 phút */
    private const DEFAULT_DURATION = 120;

    /** Thời gian chiếu mặc định của một phim tại rạp */
    private const RUN_WEEKS = 8;

    /** @return array{created: int, updated: int} */
    public function import(): array
    {
        $data = json_decode(File::get(database_path(self::DATA_FILE)), true);
        $result = ['created' => 0, 'updated' => 0];

        foreach ($data['movies'] as $item) {
            $movie = Movie::withTrashed()->where('title', $item['title'])->first();

            if ($movie) {
                $result['updated'] += (int) $this->refresh($movie, $item);

                continue;
            }

            $movie = Movie::create([
                'title' => $item['title'],
                'description' => $item['description'],
                'director' => $item['director'],
                'cast' => $item['cast'],
                'duration' => $item['duration'] ?? self::DEFAULT_DURATION,
                'release_date' => $item['release_date'] ?? today()->toDateString(),
                'end_date' => $this->endDate($item),
                'age_rating' => $item['age_rating'],
                'status' => $item['status'],
                'trailer_url' => $item['trailer_url'],
                'poster' => $this->copyPoster($item['poster']),
            ]);

            $genreIds = collect($item['genres'])->map(fn (string $name) => Genre::firstOrCreate(['name' => $name])->id);
            $movie->genres()->sync($genreIds);
            $result['created']++;
        }

        return $result;
    }

    /**
     * Phim đã có: chỉ cập nhật khi Moveek cho biết phim đã ra rạp hoặc vẫn đang chiếu
     * (không ghi đè phim admin đã ẩn hoặc tự cho ngừng chiếu).
     */
    private function refresh(Movie $movie, array $item): bool
    {
        if ($movie->trashed() || $movie->status === 'ended' || $item['status'] !== 'now_showing') {
            return false;
        }

        $movie->status = 'now_showing';
        if ($movie->end_date && $movie->end_date->lt(today()->addWeek())) {
            $movie->end_date = today()->addWeeks(2); // Vẫn còn suất trên Moveek: gia hạn thêm
        }

        return $movie->isDirty() && $movie->save();
    }

    /**
     * Ngày kết thúc chiếu ước tính (Moveek không công bố):
     * - Đang chiếu / sắp chiếu: 8 tuần sau khởi chiếu, phim chiếu lại (ngày khởi chiếu cũ) chiếu thêm 2–4 tuần.
     * - Đã ngừng chiếu: 8 tuần sau khởi chiếu nhưng chậm nhất là hôm qua.
     */
    private function endDate(array $item): string
    {
        $release = Carbon::parse($item['release_date'] ?? today());
        $end = $release->copy()->addWeeks(self::RUN_WEEKS);

        return match ($item['status']) {
            'ended' => $end->min(today()->subDay())->toDateString(),
            'now_showing' => $end->max(today()->addWeeks(2 + crc32($item['title']) % 3))->toDateString(),
            default => $end->toDateString(),
        };
    }

    /** Chép poster từ database/seeders/data/posters sang storage/app/public/posters */
    private function copyPoster(?string $file): ?string
    {
        $source = database_path('seeders/data/posters/'.$file);

        if (! $file || ! is_file($source)) {
            return null;
        }

        $path = 'posters/'.$file;
        Storage::disk('public')->put($path, File::get($source));

        return $path;
    }
}
