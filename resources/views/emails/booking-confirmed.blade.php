@php $st = $booking->showtime; @endphp
<x-mail::message>
# Đặt vé thành công!

Xin chào **{{ $booking->user->name }}**, cảm ơn bạn đã đặt vé tại CineBook. Dưới đây là thông tin vé của bạn:

<x-mail::panel>
**{{ $st->movie->title }}** ({{ $st->movie->age_rating }})<br>
{{ $st->start_time->translatedFormat('l, d/m/Y') }} lúc **{{ $st->start_time->format('H:i') }}** · {{ $st->room->name }}
</x-mail::panel>

<x-mail::table>
| | |
|:--|--:|
| Mã vé | **{{ $booking->code }}** |
| Ghế | {{ $booking->seat_list }} |
@if ($booking->combos->isNotEmpty())
| Bắp nước | {{ $booking->combo_list }} ({{ number_format($booking->combo_total, 0, ',', '.') }} ₫) |
@endif
@if ($booking->discount)
| Tạm tính | {{ number_format($booking->subtotal, 0, ',', '.') }} ₫ |
| Giảm giá ({{ $booking->voucher?->code }}) | -{{ number_format($booking->discount, 0, ',', '.') }} ₫ |
@endif
| Tổng tiền | **{{ number_format($booking->total_price, 0, ',', '.') }} ₫** |
</x-mail::table>

<p style="text-align: center">
{{-- Nhúng ảnh PNG vào email (cid) vì Gmail chặn ảnh SVG/data URI; khi xem trước email không có $message thì dùng SVG --}}
<img src="{{ isset($message) ? $message->embedData(\App\Services\TicketQr::png($booking->code), "qr-{$booking->code}.png", 'image/png') : \App\Services\TicketQr::svg($booking->code) }}" alt="Mã QR vé {{ $booking->code }}" width="180" height="180">
</p>

Vui lòng đưa mã QR hoặc mã vé cho nhân viên tại quầy để vào phòng chiếu. Bạn có thể thanh toán online qua VNPay trên trang vé điện tử hoặc trả tại quầy khi nhận vé, **hạn thanh toán {{ $booking->paymentDeadline()->format('H:i d/m/Y') }}** (quá hạn vé sẽ tự hủy để mở bán lại ghế). Vé chưa thanh toán có thể hủy trước giờ chiếu {{ config('cinema.cancel_before_minutes') }} phút.

<x-mail::button :url="route('bookings.show', $booking)">
Xem vé điện tử
</x-mail::button>

Chúc bạn xem phim vui vẻ!<br>
Đội ngũ CineBook
</x-mail::message>
