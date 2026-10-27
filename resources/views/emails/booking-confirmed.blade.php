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
| Tổng tiền | **{{ number_format($booking->total_price, 0, ',', '.') }} ₫** |
</x-mail::table>

<p style="text-align: center">
<img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($booking->code) }}" alt="Mã QR vé {{ $booking->code }}" width="180" height="180">
</p>

Vui lòng đưa mã QR hoặc mã vé cho nhân viên tại quầy để vào phòng chiếu. Bạn có thể hủy vé trước giờ chiếu {{ config('cinema.cancel_before_minutes') }} phút.

<x-mail::button :url="route('bookings.show', $booking)">
Xem vé điện tử
</x-mail::button>

Chúc bạn xem phim vui vẻ!<br>
Đội ngũ CineBook
</x-mail::message>
