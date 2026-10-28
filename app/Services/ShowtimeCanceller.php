<?php

namespace App\Services;

use App\Exceptions\RefundFailedException;
use App\Models\Booking;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Rạp hủy cả suất chiếu (sự cố máy chiếu, mất điện, phim bị rút...):
 * hủy mọi vé của suất, vé đã trả qua VNPay được hoàn tự động, vé trả tại quầy (hoặc VNPay lỗi)
 * chuyển sang "Chờ hoàn tiền" để nhân viên xử lý. Cuối cùng suất chiếu bị xóa mềm để ẩn khỏi trang khách.
 */
class ShowtimeCanceller
{
    public function __construct(private BookingRefunder $refunder) {}

    /** @return array{cancelled: int, refunded: int, pending: int} */
    public function cancel(Showtime $showtime, User $admin, string $ip): array
    {
        $summary = ['cancelled' => 0, 'refunded' => 0, 'pending' => 0];

        $bookings = $showtime->bookings()
            ->where(fn ($q) => $q->where('status', Booking::STATUS_CONFIRMED)->orWhere(fn ($q) => $q->pendingRefund()))
            ->get();

        foreach ($bookings as $booking) {
            if ($booking->isConfirmed()) {
                $summary['cancelled']++;
            }

            if ($booking->isPaid() && $booking->canBeRefundedViaVnpay()) {
                try {
                    $this->refunder->refund($booking, Booking::REFUND_VNPAY, null, $admin, $ip, Booking::CANCEL_SHOWTIME, notify: false);
                    $summary['refunded']++;
                    BookingRefunder::notify($booking);

                    continue;
                } catch (RefundFailedException $e) {
                    report($e); // VNPay lỗi: hủy vé, để nhân viên hoàn lại sau
                }
            }

            if ($booking->isPaid()) {
                $summary['pending']++;
            }
            DB::transaction(function () use ($booking) {
                $fresh = Booking::lockForUpdate()->find($booking->id);
                if ($fresh->isConfirmed()) {
                    $fresh->cancel(Booking::CANCEL_SHOWTIME);
                }
            });
            BookingRefunder::notify($booking);
        }

        $showtime->delete();

        return $summary;
    }
}
