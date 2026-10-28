@props(['showtime'])

{{-- Nút giờ chiếu: khách / khách hàng bấm để đặt vé; tài khoản quản trị, nhân viên không đặt vé được nên chỉ hiển thị --}}
@if (auth()->user()?->isCustomer() ?? true)
    <a href="{{ route('bookings.create', $showtime) }}" class="cb-time-btn">{{ $slot }}</a>
@else
    <span class="cb-time-btn is-disabled" title="Chỉ tài khoản khách hàng mới đặt vé được">{{ $slot }}</span>
@endif
