<?php

namespace App\Services;

use App\Mail\BookingReleased;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Tự hủy vé chưa thanh toán đã quá hạn (trước giờ chiếu 30 phút) để mở bán lại ghế,
 * tránh trường hợp đặt giữ nhiều ghế đẹp rồi không đến.
 */
class UnpaidBookingReleaser
{
    /** @return int Số vé đã hủy */
    public function release(bool $notify = true): int
    {
        $released = 0;

        // Vé có thể đã quá hạn: suất chiếu bắt đầu trong vòng 30 phút tới (hoặc đã bắt đầu); hạn chính xác tính ở paymentDeadline()
        $candidates = Booking::with(['user', 'showtime.movie', 'showtime.room', 'seats'])
            ->where('status', Booking::STATUS_CONFIRMED)
            ->whereNull('paid_at')
            ->whereNull('checked_in_at')
            ->whereHas('showtime', fn ($q) => $q->where('start_time', '<=', now()->addMinutes((int) config('cinema.unpaid_release_minutes'))))
            ->get()
            ->filter(fn (Booking $b) => $b->paymentDeadline()->lte(now()));

        foreach ($candidates as $booking) {
            // Khóa dòng và kiểm tra lại: khách có thể vừa thanh toán / được soát vé đúng lúc này
            $cancelled = DB::transaction(function () use ($booking) {
                $fresh = Booking::lockForUpdate()->find($booking->id);
                if (! $fresh->isConfirmed() || $fresh->isPaid() || $fresh->isCheckedIn()) {
                    return false;
                }
                $fresh->cancel(Booking::CANCEL_UNPAID);

                return true;
            });

            if (! $cancelled) {
                continue;
            }
            $released++;

            if ($notify) {
                try {
                    Mail::to($booking->user)->send(new BookingReleased($booking->fresh(['user', 'showtime.movie', 'showtime.room', 'seats'])));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return $released;
    }
}
