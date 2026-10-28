@extends('layouts.app')

@section('title', 'Lịch chiếu')

@section('content')
    <div class="container py-4 py-lg-5">
        <h1 class="cb-section-title mb-1">Lịch chiếu</h1>
        <p class="text-muted mb-4">Chọn ngày để xem các suất chiếu còn nhận đặt vé.</p>
        @auth
            @unless (auth()->user()->isCustomer())
                <p class="small text-warning mt-n2 mb-4"><i class="bi bi-info-circle me-1"></i>Bạn đang đăng nhập bằng tài khoản {{ mb_strtolower(auth()->user()->role_label) }} nên chỉ xem được lịch chiếu. Hãy dùng tài khoản khách hàng để đặt vé.</p>
            @endunless
        @endauth

        <div class="cb-date-tabs mb-4">
            @foreach ($days as $day)
                <a href="{{ route('schedule.index', ['ngay' => $day->toDateString()]) }}" class="cb-date-tab {{ $day->isSameDay($date) ? 'active' : '' }}">
                    <small>{{ $day->isToday() ? 'Hôm nay' : $day->translatedFormat('l') }}</small>
                    <strong>{{ $day->format('d/m') }}</strong>
                </a>
            @endforeach
        </div>

        @forelse ($showtimes as $items)
            @php $movie = $items->first()->movie; @endphp
            <div class="cb-card p-3 p-md-4 mb-3">
                <div class="d-flex gap-3 gap-md-4">
                    <a href="{{ route('movies.show', $movie) }}" class="flex-shrink-0" style="width: 110px">
                        <x-poster :movie="$movie" />
                    </a>
                    <div class="flex-grow-1 min-w-0">
                        <h2 class="h5 fw-bold mb-1">
                            <a href="{{ route('movies.show', $movie) }}" class="text-body text-decoration-none">{{ $movie->title }}</a>
                            @if ($movie->status === 'coming_soon')
                                <span class="badge rounded-pill text-bg-warning align-middle ms-1" style="font-size: .7rem">Đặt vé trước</span>
                            @endif
                        </h2>
                        <div class="text-muted small mb-3">
                            <span class="cb-age position-static {{ $movie->age_rating }} me-1">{{ $movie->age_rating }}</span>
                            {{ $movie->duration }} phút · {{ $movie->genres->pluck('name')->join(', ') }}
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($items as $showtime)
                                <x-time-button :showtime="$showtime">
                                    <strong>{{ $showtime->start_time->format('H:i') }}</strong>
                                    <small>{{ $showtime->room->name }}</small>
                                </x-time-button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="cb-card cb-empty">
                <i class="bi bi-calendar-x"></i>
                Không còn suất chiếu nào trong ngày {{ $date->format('d/m/Y') }}.
            </div>
        @endforelse
    </div>
@endsection
