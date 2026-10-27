@props(['voucher'])

@php
    $money = fn (int $n) => number_format($n, 0, ',', '.').' ₫';
    $upcoming = $voucher->starts_at?->isFuture();
    $remaining = $voucher->usage_limit !== null ? $voucher->usage_limit - $voucher->used_count : null;
@endphp

{{-- Mã giảm giá dạng vé: bên trái mức giảm, bên phải điều kiện + nút sao chép mã --}}
<div class="cb-voucher {{ $upcoming ? 'is-upcoming' : '' }}">
    <div class="cb-voucher-value">
        @if ($voucher->type === \App\Models\Voucher::TYPE_PERCENT)
            <strong>{{ $voucher->value }}%</strong>
        @else
            <strong>{{ intdiv($voucher->value, 1000) }}K</strong>
        @endif
        <small>GIẢM</small>
    </div>
    <div class="cb-voucher-body">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <h3 class="h6 fw-bold mb-0">{{ $voucher->description ?: $voucher->label }}</h3>
            @if ($upcoming)
                <span class="badge rounded-pill text-bg-info">Từ {{ $voucher->starts_at->format('d/m') }}</span>
            @elseif ($remaining !== null && $remaining <= 10)
                <span class="badge rounded-pill text-bg-warning">Chỉ còn {{ $remaining }} lượt</span>
            @endif
        </div>
        <ul class="cb-voucher-terms">
            <li>{{ $voucher->label }}</li>
            <li>{{ $voucher->min_order ? 'Đơn từ '.$money($voucher->min_order) : 'Áp dụng mọi đơn' }} · mỗi khách 1 lần</li>
            @if ($voucher->expires_at)
                <li>Hạn dùng đến {{ $voucher->expires_at->format('d/m/Y') }}</li>
            @endif
        </ul>
        <div class="d-flex align-items-center gap-2 mt-2">
            <code class="cb-voucher-code">{{ $voucher->code }}</code>
            <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" data-copy="{{ $voucher->code }}" @disabled($upcoming)>
                <i class="bi bi-copy me-1"></i><span>Sao chép</span>
            </button>
        </div>
    </div>
</div>
