<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Nạp phim thật từ database/seeders/data/movies.json.
 * File này được tạo bởi lệnh "php artisan movies:crawl" (cào từ Moveek.com).
 */
class MovieSeeder extends Seeder
{
    /** Phim sắp chiếu chưa công bố thời lượng thì tạm tính 120 phút */
    private const DEFAULT_DURATION = 120;

    public function run(): void
    {
        $data = json_decode(File::get(__DIR__.'/data/movies.json'), true);

        foreach ($data['movies'] as $item) {
            $movie = Movie::create([
                'title' => $item['title'],
                'description' => $item['description'],
                'director' => $item['director'],
                'cast' => $item['cast'],
                'duration' => $item['duration'] ?? self::DEFAULT_DURATION,
                'release_date' => $item['release_date'] ?? now()->toDateString(),
                'age_rating' => $item['age_rating'],
                'status' => $item['status'],
                'trailer_url' => $item['trailer_url'],
                'poster' => $this->copyPoster($item['poster']),
            ]);

            $genreIds = collect($item['genres'])->map(fn (string $name) => Genre::firstOrCreate(['name' => $name])->id);
            $movie->genres()->sync($genreIds);
        }
    }

    /** Chép poster từ database/seeders/data/posters sang storage/app/public/posters */
    private function copyPoster(?string $file): ?string
    {
        $source = __DIR__.'/data/posters/'.$file;

        if (! $file || ! is_file($source)) {
            return null;
        }

        $path = 'posters/'.$file;
        Storage::disk('public')->put($path, File::get($source));

        return $path;
    }
}
