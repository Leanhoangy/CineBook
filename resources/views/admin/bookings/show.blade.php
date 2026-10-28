@extends('layouts.admin')

@section('title', 'Đơn '.$booking->code)

@section('content')
    @php $st = $booking->showtime; @endphp
    <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-light mb-3"><i class="bi bi-arrow-left me-1"></i>Danh sách đơn</a>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="ad-card p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small">Mã vé</div>
                        <div class="fs-4 fw-bold font-monospace">{{ $booking->code }}</div>
                    </div>
                    <div class="text-end">
                        <x-status-badge :status="$booking->status" class="fs-6" />
                        @if ($booking->cancel_reason_label)
                            <div class="small text-muted mt-1">{{ $booking->cancel_reason_label }}</div>
                        @endif
                        <x-payment-badge :booking="$booking" class="d-table ms-auto mt-1" />
                    </div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="text-muted small">Phim</div>
                        <div class="fw-semibold">{{ $st->movie->title }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Suất chiếu</div>
                        <div class="fw-semibold">{{ $st->start_time->format('H:i d/m/Y') }} · {{ $st->room->name }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Ngày đặt</div>
                        <div>{{ $booking->created_at->format('H:i:s d/m/Y') }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Thanh toán</div>
                        <div>
                            @if ($booking->isPaid())
                                {{ \App\Models\Booking::PAYMENT_METHODS[$booking->payment_method] }} lúc {{ $booking->paid_at->format('H:i d/m/Y') }}
                                @if ($booking->payment_ref)<span class="small text-muted">· mã GD <span class="font-monospace">{{ $booking->payment_ref }}</span></span>@endif
                            @else
                                Chưa thanh toán
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Soát vé</div>
                        <div>{!! $booking->isCheckedIn() ? 'Đã vào rạp lúc '.$booking->checked_in_at->format('H:i d/m/Y') : '<span class="text-muted">Chưa soát</span>' !!}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Ghi chú</div>
                        <div>{{ $booking->note ?: '—' }}</div>
                    </div>
                </div>
                <table class="table ad-table border rounded">
                    <thead><tr><th>Ghế</th><th>Loại</th><th class="text-end">Giá</th></tr></thead>
                    <tbody>
                        @foreach ($booking->seats as $seat)
                            <tr>
                                <td class="fw-semibold">{{ $seat->seat_code }}</td>
                                <td>{!! $seat->seat_type === 'vip' ? '<span class="badge text-bg-warning">VIP</span>' : 'Thường' !!}</td>
                                <td class="text-end">@money($seat->price)</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @foreach ($booking->combos as $combo)
                            <tr><td colspan="2">🍿 {{ $combo->name }} × {{ $combo->quantity }}</td><td class="text-end">@money($combo->amount)</td></tr>
                        @endforeach
                        @if ($booking->discount)
                            <tr><td colspan="2" class="text-muted">Tạm tính</td><td class="text-end">@money($booking->subtotal)</td></tr>
                            <tr><td colspan="2" class="text-success">Mã giảm giá <span class="font-monospace">{{ $booking->voucher?->code ?? '(đã xóa)' }}</span></td><td class="text-end text-success">-@money($booking->discount)</td></tr>
                        @endif
                        <tr><th colspan="2">Tổng cộng</th><th class="text-end fs-5">@money($booking->total_price)</th></tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="ad-card p-4 mb-4">
                <h2 class="h6 fw-bold mb-3">Khách hàng</h2>
                <div class="fw-semibold">{{ $booking->user->name }}</div>
                <div class="small text-muted">{{ $booking->user->email }}</div>
                <div class="small text-muted">{{ $booking->user->phone ?: 'Chưa có SĐT' }}</div>
            </div>
            @if ($booking->isConfirmed() && ! $booking->isPaid())
                <form method="POST" action="{{ route('admin.bookings.mark-paid', $booking) }}" class="ad-card p-4 mb-4" data-confirm="Xác nhận đã thu @money($booking->total_price) cho vé {{ $booking->code }}?">
                    @csrf @method('PATCH')
                    <h2 class="h6 fw-bold mb-2">Thu tiền tại quầy</h2>
                    <p class="small text-muted mb-3">Khách chưa thanh toán online. Bấm khi đã thu đủ tiền lúc khách nhận vé.</p>
                    <button class="btn btn-success w-100"><i class="bi bi-cash-coin me-1"></i>Đã thu @money($booking->total_price)</button>
                </form>
            @endif
            <div class="ad-card p-4">
                <h2 class="h6 fw-bold mb-3">Cập nhật trạng thái</h2>
                <form method="POST" action="{{ route('admin.bookings.update', $booking) }}">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="form-select mb-2 @error('status') is-invalid @enderror">
                        @foreach (\App\Models\Booking::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $booking->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <input type="text" name="note" value="{{ old('note', $booking->note) }}" class="form-control mb-3 @error('note') is-invalid @enderror" placeholder="Ghi chú">
                    @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <button class="btn btn-primary w-100">Lưu</button>
                </form>
                @unless ($booking->isConfirmed())
                    <form method="POST" action="{{ route('admin.bookings.destroy', $booking) }}" class="mt-2" data-confirm="Xóa vĩnh viễn đơn {{ $booking->code }}?">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger w-100"><i class="bi bi-trash me-1"></i>Xóa đơn đã hủy</button>
                    </form>
                @endunless
            </div>
        </div>
    </div>
@endsection
