@extends('layouts.admin')

@section('title', 'Đơn đặt vé')
@section('subtitle', $bookings->total().' đơn')

@section('content')
    <div class="ad-card">
        <div class="ad-card-header">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width: 230px" placeholder="Mã vé, khách hàng, tên phim...">
                <select name="status" class="form-select form-select-sm w-auto">
                    <option value="">Mọi trạng thái</option>
                    @foreach (\App\Models\Booking::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="payment" class="form-select form-select-sm w-auto">
                    <option value="">Mọi thanh toán</option>
                    <option value="unpaid" @selected(request('payment') === 'unpaid')>Chưa thanh toán</option>
                    @foreach (\App\Models\Booking::PAYMENT_METHODS as $value => $label)
                        <option value="{{ $value }}" @selected(request('payment') === $value)>Đã trả · {{ $label }}</option>
                    @endforeach
                </select>
                <span class="small text-muted">Từ</span>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm w-auto">
                <span class="small text-muted">đến</span>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm w-auto">
                <button class="btn btn-sm btn-light"><i class="bi bi-funnel me-1"></i>Lọc</button>
                @if (request()->hasAny(['q', 'status', 'payment', 'from', 'to']))
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-link text-muted">Xóa lọc</a>
                @endif
            </form>
            <a href="{{ route('admin.bookings.export', request()->only(['q', 'status', 'payment', 'from', 'to'])) }}" class="btn btn-sm btn-success" title="Xuất các đơn theo bộ lọc hiện tại">
                <i class="bi bi-file-earmark-excel me-1"></i>Xuất Excel
            </a>
        </div>
        <div class="table-responsive">
            <table class="table ad-table table-hover">
                <thead><tr><th>Mã vé</th><th>Khách hàng</th><th>Phim / Suất chiếu</th><th>Ghế</th><th class="text-end">Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr data-href="{{ route('admin.bookings.show', $booking) }}">
                            <td class="text-nowrap">
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="fw-semibold font-monospace text-reset">{{ $booking->code }}</a>
                                <small class="d-block text-muted" title="Ngày đặt">{{ $booking->created_at->format('H:i d/m/Y') }}</small>
                            </td>
                            <td>
                                <div>{{ $booking->user->name }}</div>
                                <small class="text-muted">{{ $booking->user->email }}</small>
                            </td>
                            <td>
                                <div>{{ $booking->showtime->movie->title }}</div>
                                <small class="text-muted">{{ $booking->showtime->start_time->format('H:i d/m/Y') }} · {{ $booking->showtime->room->name }}</small>
                            </td>
                            <td class="small">{{ $booking->seat_list }}</td>
                            <td class="text-end fw-semibold text-nowrap">@money($booking->total_price)</td>
                            <td>
                                <x-status-badge :status="$booking->status" />
                                <div class="mt-1"><x-payment-badge :booking="$booking" /></div>
                            </td>
                            <td class="text-end"><a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-light btn-icon" title="Chi tiết"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="ad-empty"><i class="bi bi-receipt"></i>Không tìm thấy đơn đặt vé.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($bookings->hasPages())
            <div class="p-3 border-top">{{ $bookings->links() }}</div>
        @endif
    </div>
@endsection
