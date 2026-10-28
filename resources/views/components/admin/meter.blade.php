@props(['value'])

{{-- Thanh tỉ lệ (0–100%) kèm số phần trăm: dùng cho tỉ lệ lấp đầy ghế --}}
<div class="d-flex align-items-center gap-2">
    <div class="progress flex-grow-1" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100" style="height: 8px">
        <div class="progress-bar" style="width: {{ min($value, 100) }}%; background: var(--ad-accent)"></div>
    </div>
    <span class="small fw-semibold text-nowrap" style="width: 48px; text-align: right">{{ number_format($value, 1, ',', '.') }}%</span>
</div>
