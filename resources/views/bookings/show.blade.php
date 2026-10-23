@extends('layouts.app')

@section('title', 'Vé '.$booking->code)

@section('content')
    @php $st = $booking->showtime; @endphp
    <div class="container py-4 py-lg-5" style="max-width: 720px">
        <a href="{{ route('bookings.index') }}" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Vé của tôi</a>

        <div class="cb-ticket mt-3 {{ $booking->isConfirmed() ? '' : 'cancelled' }}">
            <div class="cb-ticket-head d-flex gap-3">
                <x-poster :movie="$st->movie" style="width: 90px; flex-shrink: 0; border-radius: 10px" />
                <div class="flex-grow-1">
                    <x-status-badge :status="$booking->status" class="mb-2" />
                    <h1 class="h4 fw-bold mb-1">{{ $st->movie->title }}</h1>
                    <div class="text-muted small">{{ $st->movie->duration }} phút · {{ $st->movie->age_rating }}</div>
                </div>
            </div>

            <div class="p-4">
                <div class="row g-3">
                    <div class="col-6 col-md-4"><div class="label">Ngày chiếu</div><div class="value">{{ $st->start_time->format('d/m/Y') }}</div></div>
                    <div class="col-6 col-md-4"><div class="label">Giờ chiếu</div><div class="value">{{ $st->start_time->format('H:i') }}</div></div>
                    <div class="col-6 col-md-4"><div class="label">Phòng</div><div class="value">{{ $st->room->name }}</div></div>
                    <div class="col-12">
                        <div class="label mb-1">Ghế ({{ $booking->seats->count() }})</div>
                        @foreach ($booking->seats as $seat)
                            <span class="badge rounded-pill {{ $seat->seat_type === 'vip' ? 'text-bg-warning' : 'text-bg-secondary' }} fs-6 me-1 mb-1">{{ $seat->seat_code }}</span>
                        @endforeach
                    </div>
                    @if ($booking->note)
                        <div class="col-12"><div class="label">Ghi chú</div><div>{{ $booking->note }}</div></div>
                    @endif
                </div>
            </div>

            <div class="cb-ticket-divider"></div>

            <div class="p-4 d-flex flex-wrap gap-4 align-items-center justify-content-between">
                <div>
                    <div class="label">Mã vé</div>
                    <div class="cb-ticket-code">{{ $booking->code }}</div>
                    <div class="label mt-3">Tổng tiền</div>
                    <div class="fs-4 fw-bold text-danger">@money($booking->total_price)</div>
                    <div class="small text-muted mt-1">Đặt lúc {{ $booking->created_at->format('H:i d/m/Y') }}</div>
                </div>
                @if ($booking->isConfirmed())
                    <img class="cb-qr" alt="Mã QR vé {{ $booking->code }}"
                         src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($booking->code) }}">
                @endif
            </div>

            <div class="cb-ticket-divider"></div>

            <div class="p-4 small">
                <h2 class="h6 fw-bold mb-2">Chi tiết thanh toán</h2>
                @foreach ($booking->seats->groupBy('seat_type') as $type => $seats)
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">{{ $type === 'vip' ? 'Ghế VIP' : 'Ghế thường' }} × {{ $seats->count() }}</span>
                        <span>@money($seats->sum('price'))</span>
                    </div>
                @endforeach
                <div class="d-flex justify-content-between pt-2 mt-1 border-top fw-bold">
                    <span>Tổng cộng</span><span>@money($booking->total_price)</span>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 justify-content-between mt-4">
            <button type="button" class="btn btn-outline-light rounded-pill px-4" onclick="window.print()"><i class="bi bi-printer me-1"></i>In vé</button>
            @can('cancel', $booking)
                <form method="POST" action="{{ route('bookings.cancel', $booking) }}" data-confirm="Bạn chắc chắn muốn hủy vé {{ $booking->code }}?">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-outline-danger rounded-pill px-4"><i class="bi bi-x-circle me-1"></i>Hủy vé</button>
                </form>
            @endcan
        </div>
    </div>
@endsection
