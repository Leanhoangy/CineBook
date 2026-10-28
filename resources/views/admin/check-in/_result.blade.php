{{-- Kết quả tra cứu / soát vé. $justCheckedIn: vừa cho vào xong; $error: lỗi khi bấm cho vào --}}
@php
    $st = $booking->showtime;
    $justCheckedIn ??= false;
    $error ??= null;
    $problem = $justCheckedIn ? null : ($error ?? $booking->checkInProblem());
    [$state, $icon, $title] = match (true) {
        $justCheckedIn => ['success', 'bi-check-circle-fill', 'Mời vào '.$st->room->name],
        (bool) $problem => ['danger', 'bi-x-octagon-fill', 'Không cho vào'],
        ! $booking->isPaid() => ['warning', 'bi-cash-coin', 'Vé chưa thanh toán'],
        default => ['success', 'bi-ticket-perforated-fill', 'Vé hợp lệ'],
    };
@endphp

<div class="ad-checkin-result is-{{ $state }}" data-code="{{ $booking->code }}">
    <div class="ad-checkin-state">
        <i class="bi {{ $icon }}"></i>
        <div>
            <div class="fw-bold fs-5">{{ $title }}</div>
            @if ($problem)
                <div>{{ $problem }}</div>
            @elseif ($justCheckedIn)
                <div>Đã soát lúc {{ $booking->checked_in_at->format('H:i') }} · ghế {{ $booking->seat_list }}</div>
            @elseif (! $booking->isPaid())
                <div>Thu <strong>@money($booking->total_price)</strong> trước khi cho khách vào.</div>
            @else
                <div>{{ $booking->payment_label }}</div>
            @endif
        </div>
    </div>

    <div class="p-3 p-lg-4">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
            <div>
                <div class="small text-muted">Mã vé</div>
                <div class="fs-5 fw-bold font-monospace">{{ $booking->code }}</div>
            </div>
            <div class="text-end">
                <x-status-badge :status="$booking->status" />
                <div class="mt-1"><x-payment-badge :booking="$booking" /></div>
            </div>
        </div>
        <div class="fw-bold fs-5 mb-1">{{ $st->movie->title }} <span class="badge text-bg-dark align-middle">{{ $st->movie->age_rating }}</span></div>
        <div class="row g-2 small mb-3">
            <div class="col-6"><span class="text-muted">Suất chiếu</span><div class="fw-semibold fs-6">{{ $st->start_time->format('H:i') }} · {{ $st->start_time->format('d/m/Y') }}</div></div>
            <div class="col-6"><span class="text-muted">Phòng</span><div class="fw-semibold fs-6">{{ $st->room->name }}</div></div>
            <div class="col-6"><span class="text-muted">Khách hàng</span><div class="fw-semibold">{{ $booking->user->name }}</div></div>
            <div class="col-6"><span class="text-muted">Tổng tiền</span><div class="fw-semibold">@money($booking->total_price)</div></div>
        </div>
        <div class="small text-muted mb-1">Ghế ({{ $booking->seats->count() }})</div>
        <div class="mb-3">
            @foreach ($booking->seats as $seat)
                <span class="badge rounded-pill {{ $seat->seat_type === 'vip' ? 'text-bg-warning' : 'text-bg-secondary' }} fs-6 me-1 mb-1">{{ $seat->seat_code }}</span>
            @endforeach
        </div>
        @if ($booking->combos->isNotEmpty())
            <div class="ad-checkin-combos mb-3">
                <i class="bi bi-cup-straw me-1"></i><strong>Giao bắp nước:</strong> {{ $booking->combo_list }}
            </div>
        @endif

        @if (! $justCheckedIn && ! $problem)
            <button type="button" class="btn btn-lg w-100 {{ $booking->isPaid() ? 'btn-success' : 'btn-warning' }}"
                    data-checkin-url="{{ route('admin.check-in.store', $booking) }}" data-collect="{{ $booking->isPaid() ? 0 : 1 }}">
                @if ($booking->isPaid())
                    <i class="bi bi-door-open me-1"></i>Cho vào
                @else
                    <i class="bi bi-cash-coin me-1"></i>Đã thu @money($booking->total_price) &amp; cho vào
                @endif
            </button>
        @endif
        <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-link btn-sm w-100 mt-1" target="_blank">Xem chi tiết đơn</a>
    </div>
</div>
