<?php

namespace Database\Seeders;

use App\Services\MovieImporter;
use Illuminate\Database\Seeder;

/**
 * Nạp phim thật từ database/seeders/data/movies.json.
 * File này được tạo bởi lệnh "php artisan movies:crawl" (cào từ Moveek.com).
 */
class MovieSeeder extends Seeder
{
    public function run(MovieImporter $importer): void
    {
        $importer->import();
    }
}
