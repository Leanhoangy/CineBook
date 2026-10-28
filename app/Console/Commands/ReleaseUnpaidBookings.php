<?php

namespace App\Console\Commands;

use App\Services\UnpaidBookingReleaser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** Chạy mỗi phút (xem routes/console.php): hủy vé chưa thanh toán đã quá hạn để mở bán lại ghế */
#[Signature('bookings:release-unpaid')]
#[Description('Tự hủy vé chưa thanh toán quá hạn (trước giờ chiếu 30 phút) và mở bán lại ghế')]
class ReleaseUnpaidBookings extends Command
{
    public function handle(UnpaidBookingReleaser $releaser): int
    {
        $released = $releaser->release();

        $this->info("Đã tự hủy {$released} vé chưa thanh toán quá hạn.");

        return self::SUCCESS;
    }
}
