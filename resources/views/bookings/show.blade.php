@extends('layouts.app')

@section('title', 'Vé '.$booking->code)

@section('content')
    @php
        $st = $booking->showtime;
        $payOnline = \App\Services\VnpayGateway::isConfigured() && auth()->user()->can('pay', $booking);
    @endphp
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
                         src="{{ \App\Services\TicketQr::svg($booking->code) }}">
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
                @if ($booking->discount)
                    <div class="d-flex justify-content-between py-1 text-success">
                        <span>Mã giảm giá {{ $booking->voucher?->code }}</span>
                        <span>-@money($booking->discount)</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between pt-2 mt-1 border-top fw-bold">
                    <span>Tổng cộng</span><span>@money($booking->total_price)</span>
                </div>

                @if ($booking->isPaid())
                    <div class="cb-payment is-paid mt-3">
                        <i class="bi bi-check2-circle"></i>
                        <div>
                            <div class="fw-semibold">{{ $booking->payment_label }}</div>
                            <div class="text-muted">
                                Lúc {{ $booking->paid_at->format('H:i d/m/Y') }}
                                @if ($booking->payment_ref) · mã giao dịch <span class="font-monospace">{{ $booking->payment_ref }}</span>@endif
                            </div>
                        </div>
                    </div>
                @elseif ($booking->isConfirmed())
                    <div class="cb-payment mt-3">
                        <i class="bi bi-wallet2"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Chưa thanh toán</div>
                            <div class="text-muted">
                                @if ($payOnline)
                                    Thanh toán online bằng thẻ ATM, Visa/Master hoặc QR ngân hàng qua VNPay, hoặc trả tại quầy khi nhận vé.
                                    Vé đã thanh toán online không hỗ trợ hủy.
                                @else
                                    Vui lòng thanh toán tại quầy khi nhận vé.
                                @endif
                            </div>
                            @if ($payOnline)
                                <form method="POST" action="{{ route('bookings.pay', $booking) }}" class="mt-2">
                                    @csrf
                                    <button class="btn btn-primary rounded-pill px-4"><i class="bi bi-credit-card me-1"></i>Thanh toán @money($booking->total_price) qua VNPay</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif
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
