<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        Room::create(['name' => 'Phòng 1', 'total_rows' => 8, 'seats_per_row' => 12, 'vip_rows' => 2]);
        Room::create(['name' => 'Phòng 2', 'total_rows' => 10, 'seats_per_row' => 14, 'vip_rows' => 3]);
        Room::create(['name' => 'Phòng 3', 'total_rows' => 7, 'seats_per_row' => 10, 'vip_rows' => 2]);
        Room::create(['name' => 'Phòng 4', 'total_rows' => 9, 'seats_per_row' => 12, 'vip_rows' => 2]);
        Room::create(['name' => 'Phòng 5', 'total_rows' => 8, 'seats_per_row' => 12, 'vip_rows' => 2]);
        Room::create(['name' => 'Phòng 6', 'total_rows' => 10, 'seats_per_row' => 14, 'vip_rows' => 3]);
        Room::create(['name' => 'Phòng 7', 'total_rows' => 7, 'seats_per_row' => 10, 'vip_rows' => 2]);
        Room::create(['name' => 'Phòng 8', 'total_rows' => 9, 'seats_per_row' => 12, 'vip_rows' => 2]);
        Room::create(['name' => 'Phòng 9', 'total_rows' => 8, 'seats_per_row' => 14, 'vip_rows' => 2]);
        Room::create(['name' => 'Phòng IMAX', 'total_rows' => 12, 'seats_per_row' => 16, 'vip_rows' => 4]);
    }
}
