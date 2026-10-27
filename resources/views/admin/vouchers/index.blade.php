@extends('layouts.admin')

@section('title', 'Mã giảm giá')

@section('content')
    <div class="ad-card">
        <div class="ad-card-header">
            <form method="GET" class="d-flex gap-2">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm mã, mô tả...">
                <button class="btn btn-sm btn-light"><i class="bi bi-search"></i></button>
            </form>
            <a href="{{ route('admin.vouchers.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm mã</a>
        </div>
        <div class="table-responsive">
            <table class="table ad-table table-hover">
                <thead><tr><th>Mã</th><th>Ưu đãi</th><th>Điều kiện</th><th class="text-center">Đã dùng</th><th class="text-end">Tổng đã giảm</th><th>Thời gian</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($vouchers as $voucher)
                        @php [$color, $label] = $voucher->status; @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold font-monospace">{{ $voucher->code }}</div>
                                <small class="text-muted">{{ $voucher->description }}</small>
                            </td>
                            <td class="text-nowrap">
                                <div class="fw-semibold">{{ $voucher->type === 'percent' ? $voucher->value.'%' : number_format($voucher->value, 0, ',', '.').' ₫' }}</div>
                                @if ($voucher->max_discount)
                                    <small class="text-muted">tối đa @money($voucher->max_discount)</small>
                                @endif
                            </td>
                            <td class="small text-muted text-nowrap">{{ $voucher->min_order ? 'Đơn từ '.number_format($voucher->min_order, 0, ',', '.').' ₫' : 'Không' }}</td>
                            <td class="text-center">{{ $voucher->used_count }}{{ $voucher->usage_limit ? ' / '.$voucher->usage_limit : '' }}</td>
                            <td class="text-end text-nowrap">@money($voucher->discount_total ?? 0)</td>
                            <td class="small text-nowrap">
                                {{ $voucher->starts_at?->format('d/m/Y') ?? '—' }} → {{ $voucher->expires_at?->format('d/m/Y') ?? 'Không hạn' }}
                            </td>
                            <td><span class="badge rounded-pill text-bg-{{ $color }}">{{ $label }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.vouchers.edit', $voucher) }}" class="btn btn-sm btn-light btn-icon" title="Sửa"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.vouchers.destroy', $voucher) }}" class="d-inline" data-confirm="Xóa mã {{ $voucher->code }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light btn-icon text-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="ad-empty"><i class="bi bi-ticket-detailed"></i>Chưa có mã giảm giá nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($vouchers->hasPages())
            <div class="p-3 border-top">{{ $vouchers->links() }}</div>
        @endif
    </div>
@endsection
