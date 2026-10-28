<?php

namespace App\Services;

use App\Exceptions\RefundFailedException;
use App\Mail\BookingCancelledByCinema;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Hoàn tiền vé đã thanh toán (chính sách giống rạp thật: khách đổi ý không được hoàn,
 * chỉ hoàn khi rạp hủy suất / sự cố / khách trả tiền cho vé đã bị hủy).
 * - REFUND_VNPAY: gọi API hoàn tiền VNPay, tiền về đúng thẻ/tài khoản khách đã trả.
 * - REFUND_MANUAL: nhân viên đã trả tiền mặt tại quầy hoặc chuyển khoản, ghi lại cách trả.
 * Vé còn hiệu lực sẽ được hủy luôn để mở bán lại ghế.
 */
class BookingRefunder
{
    public function __construct(private VnpayGateway $vnpay) {}

    /** @throws RefundFailedException */
    public function refund(
        Booking $booking,
        string $method,
        ?string $note,
        User $admin,
        string $ip,
        string $cancelReason = Booking::CANCEL_BY_ADMIN,
        bool $notify = true,
    ): Booking {
        $booking = DB::transaction(function () use ($booking, $method, $note, $admin, $ip, $cancelReason) {
            // Khóa dòng để 2 nhân viên bấm cùng lúc không hoàn tiền 2 lần
            $booking = Booking::lockForUpdate()->findOrFail($booking->id);

            if (! $booking->isPaid() || $booking->isRefunded()) {
                throw new RefundFailedException("Vé {$booking->code} chưa thanh toán hoặc đã được hoàn tiền trước đó.");
            }

            $ref = $method === Booking::REFUND_VNPAY
                ? $this->vnpay->refund($booking, $admin->name, $ip)
                : $note;

            $booking->fill(['refunded_at' => now(), 'refund_method' => $method, 'refund_ref' => $ref]);
            if ($booking->isConfirmed()) {
                $booking->fill(['status' => Booking::STATUS_CANCELLED, 'cancel_reason' => $cancelReason]);
            }
            $booking->save();

            return $booking;
        });

        if ($notify) {
            self::notify($booking);
        }

        return $booking;
    }

    /** Email báo khách vé bị rạp hủy / đã được hoàn tiền. Lỗi gửi mail không làm hỏng thao tác chính. */
    public static function notify(Booking $booking): void
    {
        try {
            Mail::to($booking->user)->send(new BookingCancelledByCinema($booking->fresh(['user', 'showtime.movie', 'showtime.room', 'seats'])));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
