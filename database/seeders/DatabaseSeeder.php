<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Quản trị viên',
            'email' => 'admin@cinebook.test',
        ]);

        User::factory()->staff()->create([
            'name' => 'Trần Thị Soát Vé',
            'email' => 'soatve@cinebook.test',
        ]);

        User::factory()->create([
            'name' => 'Nguyễn Văn Khách',
            'email' => 'user@cinebook.test',
        ]);

        User::factory(1500)->create();

        $this->call([
            GenreSeeder::class,
            MovieSeeder::class,
            RoomSeeder::class,
            ShowtimeSeeder::class,
            VoucherSeeder::class,
            ComboSeeder::class,
            BookingSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
