<?php

namespace App\Console\Commands;

use App\Services\ShowtimeScheduler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Chạy hằng ngày (xem routes/console.php) để website luôn có suất chiếu cho những ngày tới,
 * và tự chuyển phim sắp chiếu sang đang chiếu khi đến ngày khởi chiếu.
 */
#[Signature('showtimes:refresh {--days=7 : Số ngày tới cần có lịch chiếu}')]
#[Description('Cập nhật trạng thái phim và tự xếp suất chiếu cho những ngày tới')]
class RefreshShowtimes extends Command
{
    public function handle(ShowtimeScheduler $scheduler): int
    {
        $released = $scheduler->updateStatuses();
        $created = $scheduler->fill(today(), today()->addDays((int) $this->option('days')));

        $this->info("Đã chuyển {$released} phim sang \"Đang chiếu\" và tạo {$created} suất chiếu mới.");

        return self::SUCCESS;
    }
}
