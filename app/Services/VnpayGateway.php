<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Thanh toán vé qua cổng VNPay (API v2.1.0).
 * - paymentUrl(): tạo link chuyển khách sang trang thanh toán của VNPay, có chữ ký HMAC-SHA512.
 * - process(): xử lý kết quả VNPay gửi về (trình duyệt quay về hoặc IPN gọi từ server VNPay),
 *   kiểm tra chữ ký + số tiền rồi ghi nhận vé đã thanh toán. Gọi nhiều lần vẫn chỉ ghi nhận 1 lần.
 */
class VnpayGateway
{
    public const RESULT_PAID = 'paid';

    public const RESULT_ALREADY_PAID = 'already_paid';

    public const RESULT_FAILED = 'failed';

    public const RESULT_CANCELLED_BY_USER = 'cancelled_by_user';

    public const RESULT_BOOKING_CANCELLED = 'booking_cancelled';

    public const RESULT_INVALID_AMOUNT = 'invalid_amount';

    public const RESULT_NOT_FOUND = 'not_found';

    public const RESULT_INVALID_SIGNATURE = 'invalid_signature';

    /** Chưa nhập mã website (TMN code) và chuỗi bí mật thì ẩn nút thanh toán online */
    public static function isConfigured(): bool
    {
        return filled(config('services.vnpay.tmn_code')) && filled(config('services.vnpay.hash_secret'));
    }

    public function paymentUrl(Booking $booking, string $ip): string
    {
        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => config('services.vnpay.tmn_code'),
            'vnp_Amount' => $booking->total_price * 100, // VNPay tính theo đơn vị 1/100 đồng
            'vnp_CurrCode' => 'VND',
            // Mỗi lần thanh toán cần mã giao dịch khác nhau: mã vé + thời điểm
            'vnp_TxnRef' => $booking->code.'-'.now()->format('His'),
            'vnp_OrderInfo' => 'Thanh toan ve xem phim '.$booking->code,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => route('payments.vnpay.return'),
            'vnp_IpAddr' => $ip,
            'vnp_CreateDate' => now()->format('YmdHis'),
            // Link hết hạn sau 15 phút, và không quá hạn thanh toán của vé (lúc đó vé sẽ tự hủy)
            'vnp_ExpireDate' => min(now()->addMinutes(15), $booking->paymentDeadline())->format('YmdHis'),
        ];

        $query = $this->signedQuery($params);

        return config('services.vnpay.url').'?'.$query.'&vnp_SecureHash='.$this->sign($query);
    }

    /** @param  array<string, string>  $params  Các tham số vnp_* VNPay gửi về */
    public function process(array $params): array
    {
        if (! $this->hasValidSignature($params)) {
            return [self::RESULT_INVALID_SIGNATURE, null];
        }

        $code = Str::before($params['vnp_TxnRef'] ?? '', '-');

        return DB::transaction(function () use ($params, $code) {
            // Khóa dòng để trình duyệt quay về và IPN đến cùng lúc không ghi nhận 2 lần
            $booking = Booking::where('code', $code)->lockForUpdate()->first();

            $result = match (true) {
                ! $booking => self::RESULT_NOT_FOUND,
                (int) ($params['vnp_Amount'] ?? 0) !== $booking->total_price * 100 => self::RESULT_INVALID_AMOUNT,
                $booking->isPaid() => self::RESULT_ALREADY_PAID,
                ($params['vnp_ResponseCode'] ?? '') === '24' => self::RESULT_CANCELLED_BY_USER,
                ($params['vnp_ResponseCode'] ?? '') !== '00' || ($params['vnp_TransactionStatus'] ?? '') !== '00' => self::RESULT_FAILED,
                ! $booking->isConfirmed() => self::RESULT_BOOKING_CANCELLED,
                default => self::RESULT_PAID,
            };

            if ($result === self::RESULT_PAID) {
                $booking->markPaid(Booking::PAYMENT_VNPAY, $params['vnp_TransactionNo'] ?? null);
            }

            return [$result, $booking];
        });
    }

    public function hasValidSignature(array $params): bool
    {
        $hash = $params['vnp_SecureHash'] ?? '';
        $data = array_filter(
            $params,
            fn ($key) => str_starts_with($key, 'vnp_') && ! in_array($key, ['vnp_SecureHash', 'vnp_SecureHashType']),
            ARRAY_FILTER_USE_KEY,
        );

        return $hash !== '' && hash_equals($this->sign($this->signedQuery($data)), strtolower($hash));
    }

    /** Chuỗi ký theo quy định VNPay: sắp xếp tham số theo tên rồi nối dạng key=value (urlencode) */
    private function signedQuery(array $params): string
    {
        ksort($params);

        return http_build_query($params);
    }

    private function sign(string $query): string
    {
        return hash_hmac('sha512', $query, (string) config('services.vnpay.hash_secret'));
    }
}
