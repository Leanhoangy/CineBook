<?php

namespace Database\Seeders;

use App\Services\ShowtimeScheduler;
use Illuminate\Database\Seeder;

/**
 * Xếp lịch chiếu trong 14 ngày trước và 7 ngày tới.
 * Ngày trong quá khứ có cả phim nay đã ngừng chiếu để lịch sử đặt vé tự nhiên hơn.
 */
class ShowtimeSeeder extends Seeder
{
    public function run(ShowtimeScheduler $scheduler): void
    {
        $scheduler->fill(today()->subDays(14), today()->addDays(7));
    }
}
