<?php

namespace App\Services;

use App\Exceptions\RefundFailedException;
use App\Models\Booking;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Thanh toán vé qua cổng VNPay (API v2.1.0).
 * - paymentUrl(): tạo link chuyển khách sang trang thanh toán của VNPay, có chữ ký HMAC-SHA512.
 * - process(): xử lý kết quả VNPay gửi về (trình duyệt quay về hoặc IPN gọi từ server VNPay),
 *   kiểm tra chữ ký + số tiền rồi ghi nhận vé đã thanh toán. Gọi nhiều lần vẫn chỉ ghi nhận 1 lần.
 * - refund(): gọi API hoàn tiền của VNPay, tiền được trả về đúng thẻ/tài khoản khách đã dùng.
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

            // Vé đã bị hủy mà khách vẫn trả tiền xong (VD: trả sát hạn đúng lúc vé tự hủy):
            // vẫn ghi nhận tiền đã nhận để vé vào danh sách "Chờ hoàn tiền"
            if (in_array($result, [self::RESULT_PAID, self::RESULT_BOOKING_CANCELLED], true)) {
                $payDate = $params['vnp_PayDate'] ?? '';
                $booking->markPaid(
                    Booking::PAYMENT_VNPAY,
                    $params['vnp_TransactionNo'] ?? null,
                    $params['vnp_TxnRef'],
                    preg_match('/^\d{14}$/', $payDate) ? Carbon::createFromFormat('YmdHis', $payDate) : null,
                );
            }

            return [$result, $booking];
        });
    }

    /**
     * Hoàn toàn bộ tiền vé qua API VNPay (vnp_Command=refund). Trả về mã giao dịch hoàn tiền của VNPay.
     *
     * @throws RefundFailedException
     */
    public function refund(Booking $booking, string $createdBy, string $ip): string
    {
        if (! self::isConfigured()) {
            throw new RefundFailedException('Chưa cấu hình VNPay nên không hoàn tiền tự động được.');
        }
        if (! $booking->canBeRefundedViaVnpay()) {
            throw new RefundFailedException('Vé không có thông tin giao dịch VNPay để hoàn tiền tự động.');
        }

        $params = [
            'vnp_RequestId' => Str::random(20),
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'refund',
            'vnp_TmnCode' => config('services.vnpay.tmn_code'),
            'vnp_TransactionType' => '02', // 02 = hoàn toàn phần
            'vnp_TxnRef' => $booking->payment_txn_ref,
            'vnp_Amount' => $booking->total_price * 100,
            'vnp_TransactionNo' => $booking->payment_ref,
            'vnp_TransactionDate' => $booking->paid_at->format('YmdHis'), // thời điểm thanh toán gốc
            'vnp_CreateBy' => Str::limit(Str::ascii($createdBy), 50, ''),
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_IpAddr' => $ip,
            'vnp_OrderInfo' => 'Hoan tien ve xem phim '.$booking->code,
        ];
        // API hoàn tiền ký theo thứ tự cố định, nối bằng dấu |
        $params['vnp_SecureHash'] = hash_hmac('sha512', implode('|', $params), (string) config('services.vnpay.hash_secret'));

        try {
            $response = Http::timeout(30)->asJson()->post(config('services.vnpay.api_url'), $params);
        } catch (ConnectionException) {
            throw new RefundFailedException('Không kết nối được tới VNPay, vui lòng thử lại.');
        }

        $code = $response->json('vnp_ResponseCode');
        if ($code !== '00') {
            throw new RefundFailedException(match ($code) {
                '91' => 'VNPay không tìm thấy giao dịch gốc (91).',
                '94' => 'Yêu cầu hoàn tiền đang được VNPay xử lý, vui lòng chờ (94).',
                '95' => 'Giao dịch không thành công hoặc đã được hoàn trước đó (95).',
                '97' => 'Sai chữ ký khi gọi VNPay (97).',
                // Môi trường sandbox: VNPay khóa API hoàn tiền, phải liên hệ VNPay để mở
                '99' => 'VNPay báo lỗi hệ thống (99). Tài khoản sandbox mặc định chưa được VNPay mở quyền hoàn tiền tự động, hãy hoàn thủ công.',
                null => 'VNPay không phản hồi đúng định dạng (HTTP '.$response->status().').',
                default => 'VNPay từ chối hoàn tiền: '.($response->json('vnp_Message') ?: 'lỗi không xác định')." ({$code}).",
            });
        }

        return (string) ($response->json('vnp_TransactionNo') ?: $params['vnp_RequestId']);
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
