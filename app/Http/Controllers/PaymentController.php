<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\VnpayGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Thanh toán vé online qua VNPay.
 * Luồng: khách bấm "Thanh toán" → sang trang VNPay → VNPay đưa khách quay về (vnpayReturn)
 * và đồng thời gọi IPN từ server VNPay (vnpayIpn) để báo kết quả, kể cả khi khách đóng trình duyệt.
 */
class PaymentController extends Controller
{
    public function __construct(private VnpayGateway $vnpay) {}

    public function pay(Request $request, Booking $booking): RedirectResponse
    {
        if (! VnpayGateway::isConfigured()) {
            return back()->with('error', 'Thanh toán online đang tạm ngưng. Bạn vui lòng thanh toán tại quầy khi nhận vé.');
        }

        if (Gate::denies('pay', $booking)) {
            return back()->with('error', 'Vé này đã thanh toán, đã hủy hoặc suất chiếu đã bắt đầu.');
        }

        return redirect()->away($this->vnpay->paymentUrl($booking, $request->ip()));
    }

    /** Trình duyệt của khách quay về từ VNPay */
    public function vnpayReturn(Request $request): RedirectResponse
    {
        [$result, $booking] = $this->vnpay->process($request->query());

        if (! $booking || $result === VnpayGateway::RESULT_INVALID_SIGNATURE) {
            return redirect()->route('bookings.index')->with('error', 'Kết quả thanh toán không hợp lệ.');
        }

        [$type, $message] = match ($result) {
            VnpayGateway::RESULT_PAID, VnpayGateway::RESULT_ALREADY_PAID => ['success', "Thanh toán vé {$booking->code} thành công. Hẹn gặp bạn ở rạp!"],
            VnpayGateway::RESULT_CANCELLED_BY_USER => ['error', 'Bạn đã hủy thanh toán. Vé vẫn được giữ, bạn có thể thanh toán lại hoặc trả tại quầy.'],
            VnpayGateway::RESULT_BOOKING_CANCELLED => ['error', 'Vé đã bị hủy trước khi thanh toán xong. Rạp đã ghi nhận số tiền bạn trả và sẽ hoàn lại vào tài khoản của bạn.'],
            default => ['error', 'Thanh toán không thành công. Bạn có thể thử lại hoặc thanh toán tại quầy.'],
        };

        return redirect()->route('bookings.show', $booking)->with($type, $message);
    }

    /** VNPay gọi từ server của họ để báo kết quả (IPN). Phải trả JSON đúng mã VNPay quy định. */
    public function vnpayIpn(Request $request): JsonResponse
    {
        [$result] = $this->vnpay->process($request->query());

        [$code, $message] = match ($result) {
            VnpayGateway::RESULT_INVALID_SIGNATURE => ['97', 'Invalid signature'],
            VnpayGateway::RESULT_NOT_FOUND => ['01', 'Order not found'],
            VnpayGateway::RESULT_INVALID_AMOUNT => ['04', 'Invalid amount'],
            VnpayGateway::RESULT_ALREADY_PAID => ['02', 'Order already confirmed'],
            default => ['00', 'Confirm Success'], // đã ghi nhận kết quả (thành công, thất bại, hoặc tiền trả cho vé đã hủy chờ hoàn)
        };

        return response()->json(['RspCode' => $code, 'Message' => $message]);
    }
}
