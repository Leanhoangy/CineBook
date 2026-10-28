@php $st = $booking->showtime; @endphp
<x-mail::message>
# Vé của bạn đã bị hủy

Xin chào **{{ $booking->user->name }}**, vé **{{ $booking->code }}** chưa được thanh toán trước hạn
({{ $booking->paymentDeadline()->format('H:i d/m/Y') }}, trước giờ chiếu {{ config('cinema.unpaid_release_minutes') }} phút) nên hệ thống đã tự hủy và mở bán lại ghế cho khách khác.

<x-mail::panel>
**{{ $st->movie->title }}**<br>
{{ $st->start_time->translatedFormat('l, d/m/Y') }} lúc **{{ $st->start_time->format('H:i') }}** · {{ $st->room->name }} · Ghế {{ $booking->seat_list }}
</x-mail::panel>

Nếu vẫn muốn xem phim, bạn có thể đặt lại vé (nếu còn ghế). Lần sau hãy thanh toán online ngay sau khi đặt để giữ chỗ chắc chắn nhé.

<x-mail::button :url="route('movies.show', $st->movie)">
Đặt lại vé
</x-mail::button>

Đội ngũ CineBook
</x-mail::message>
