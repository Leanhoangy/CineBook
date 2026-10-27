@extends('layouts.app')

@section('title', 'Vé của tôi')

@section('content')
    <div class="container py-4 py-lg-5" style="max-width: 960px">
        <h1 class="cb-section-title mb-4">Vé của tôi</h1>

        <ul class="nav nav-pills mb-4 gap-2">
            <li class="nav-item"><a class="nav-link rounded-pill {{ $tab === 'upcoming' ? 'active' : '' }}" href="{{ route('bookings.index') }}"><i class="bi bi-hourglass-split me-1"></i>Sắp chiếu</a></li>
            <li class="nav-item"><a class="nav-link rounded-pill {{ $tab === 'past' ? 'active' : '' }}" href="{{ route('bookings.index', ['tab' => 'da-xem']) }}"><i class="bi bi-clock-history me-1"></i>Đã chiếu</a></li>
        </ul>

        @forelse ($bookings as $booking)
            @php $st = $booking->showtime; @endphp
            <a href="{{ route('bookings.show', $booking) }}" class="cb-card cb-booking-item d-flex gap-3 p-3 mb-3 text-decoration-none text-body">
                <x-poster :movie="$st->movie" :show-age="false" />
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                        <h2 class="h6 fw-bold mb-1">{{ $st->movie->title }}</h2>
                        <span class="d-flex gap-1 flex-wrap">
                            <x-status-badge :status="$booking->status" />
                            <x-payment-badge :booking="$booking" />
                        </span>
                    </div>
                    <div class="small text-muted mb-2">
                        <i class="bi bi-calendar-event me-1"></i>{{ $st->start_time->translatedFormat('l, d/m/Y · H:i') }}
                        <span class="mx-1">·</span><i class="bi bi-door-open me-1"></i>{{ $st->room->name }}
                    </div>
                    <div class="d-flex justify-content-between flex-wrap gap-2 small">
                        <span>Ghế: <strong>{{ $booking->seat_list }}</strong></span>
                        <span>Mã vé: <strong class="font-monospace">{{ $booking->code }}</strong> · <strong class="text-danger">@money($booking->total_price)</strong></span>
                    </div>
                </div>
            </a>
        @empty
            <div class="cb-card cb-empty">
                <i class="bi bi-ticket-perforated"></i>
                <p class="mb-3">{{ $tab === 'past' ? 'Bạn chưa xem phim nào.' : 'Bạn chưa có vé nào cho các suất sắp chiếu.' }}</p>
                <a href="{{ route('movies.index') }}" class="btn btn-primary rounded-pill px-4">Đặt vé ngay</a>
            </div>
        @endforelse

        <div class="d-flex justify-content-center mt-4">{{ $bookings->links() }}</div>
    </div>
@endsection
