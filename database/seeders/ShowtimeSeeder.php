<?php

namespace Database\Seeders;

use App\Services\ShowtimeScheduler;
use Illuminate\Database\Seeder;

/**
 * Xếp lịch chiếu trong 35 ngày trước (config cinema.seed_history_days) và 7 ngày tới: đủ lịch sử để trang Thống kê xem trọn 1 tháng (Tháng này / 30 ngày).
 * Ngày trong quá khứ có cả phim nay đã ngừng chiếu để lịch sử đặt vé tự nhiên hơn.
 */
class ShowtimeSeeder extends Seeder
{
    public function run(ShowtimeScheduler $scheduler): void
    {
        $scheduler->fill(today()->subDays(config('cinema.seed_history_days')), today()->addDays(7), wholeDayToday: true);
    }
}
