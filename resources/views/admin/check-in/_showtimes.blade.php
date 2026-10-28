{{-- Bảng suất chiếu đang trong giờ soát vé (mở trước giờ chiếu config('cinema.checkin_before_minutes') phút) và sắp mở soát --}}
<div class="ad-card-header">
    <h2><span class="ad-live-dot"></span>Suất đang soát vé</h2>
    <span class="badge rounded-pill {{ $open->isEmpty() ? 'text-bg-secondary' : 'text-bg-danger' }}">{{ $open->count() }} suất</span>
</div>
<ul class="list-group list-group-flush">
    @forelse ($open as $st)
        @php
            $minutesLeft = (int) ceil(now()->diffInMinutes($st->start_time));
            $percent = $st->sold_count ? round($st->checked_count / $st->sold_count * 100) : 0;
        @endphp
        <li class="list-group-item ad-checkin-show">
            <div class="ad-checkin-show-time">
                <div class="fs-5 fw-bold">{{ $st->start_time->format('H:i') }}</div>
                <div class="small text-muted text-nowrap">{{ $st->room->name }}</div>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold text-truncate">{{ $st->movie->title }}</div>
                <div class="small">
                    @if ($st->start_time->isFuture())
                        <span class="{{ $minutesLeft <= 10 ? 'text-danger fw-semibold' : 'text-muted' }}">Bắt đầu sau {{ $minutesLeft }} phút</span>
                    @else
                        <span class="text-success fw-semibold">Đang chiếu</span> <span class="text-muted">· kết thúc {{ $st->end_time->format('H:i') }}</span>
                    @endif
                </div>
                <div class="progress mt-1" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" style="height: 6px">
                    <div class="progress-bar bg-success" style="width: {{ $percent }}%"></div>
                </div>
                <div class="small text-muted mt-1">
                    <strong class="text-body">{{ $st->checked_count }}/{{ $st->sold_count }}</strong> vé đã vào
                    @if ($st->unpaid_count)
                        · <span class="text-warning-emphasis">{{ $st->unpaid_count }} vé chưa thanh toán</span>
                    @endif
                </div>
            </div>
        </li>
    @empty
        <li class="list-group-item text-muted small">
            Chưa có suất nào tới giờ soát vé. Soát vé mở trước giờ chiếu {{ config('cinema.checkin_before_minutes') }} phút.
        </li>
    @endforelse
</ul>
@if ($soon->isNotEmpty())
    <div class="px-3 pt-3 pb-1 small fw-semibold text-muted text-uppercase">Sắp mở soát (2 giờ tới)</div>
    <ul class="list-group list-group-flush">
        @foreach ($soon as $st)
            <li class="list-group-item d-flex gap-2 align-items-center small">
                <span class="badge text-bg-light border">Mở {{ $st->checkInOpensAt()->format('H:i') }}</span>
                <span class="flex-grow-1 min-w-0 text-truncate">{{ $st->movie->title }}</span>
                <span class="text-muted text-nowrap">{{ $st->start_time->format('H:i') }} · {{ $st->room->name }} · {{ $st->sold_count }} vé</span>
            </li>
        @endforeach
    </ul>
@endif
