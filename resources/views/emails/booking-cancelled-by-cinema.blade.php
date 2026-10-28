@php $st = $booking->showtime; @endphp
<x-mail::message>
@if ($booking->cancel_reason === \App\Models\Booking::CANCEL_SHOWTIME)
# Suất chiếu đã bị hủy

Xin chào **{{ $booking->user->name }}**, rất xin lỗi bạn: vì sự cố ngoài ý muốn, rạp phải hủy suất chiếu dưới đây nên vé **{{ $booking->code }}** đã được hủy.
@else
# Vé của bạn đã được hoàn tiền

Xin chào **{{ $booking->user->name }}**, vé **{{ $booking->code }}** đã được hủy và hoàn tiền.
@endif

<x-mail::panel>
**{{ $st->movie->title }}**<br>
{{ $st->start_time->translatedFormat('l, d/m/Y') }} lúc **{{ $st->start_time->format('H:i') }}** · {{ $st->room->name }} · Ghế {{ $booking->seat_list }}
</x-mail::panel>

@if ($booking->isRefunded() && $booking->refund_method === \App\Models\Booking::REFUND_VNPAY)
Rạp đã hoàn **@money($booking->total_price)** qua VNPay (mã giao dịch hoàn tiền **{{ $booking->refund_ref }}**). Tiền sẽ về đúng thẻ/tài khoản bạn đã dùng để thanh toán, thường trong vài ngày làm việc tùy ngân hàng.
@elseif ($booking->isRefunded())
Rạp đã hoàn **@money($booking->total_price)** cho bạn ({{ $booking->refund_ref }}).
@elseif ($booking->needsRefund())
Rạp đã ghi nhận bạn thanh toán **@money($booking->total_price)** và sẽ hoàn lại toàn bộ số tiền này cho bạn sớm nhất. Nếu bạn trả tại quầy, vui lòng mang mã vé tới quầy để nhận lại tiền.
@else
Vé chưa thanh toán nên bạn không mất khoản phí nào.
@endif

<x-mail::button :url="route('movies.index')">
Chọn suất chiếu khác
</x-mail::button>

Đội ngũ CineBook
</x-mail::message>
