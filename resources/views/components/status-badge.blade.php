@props(['status'])

@php
    $map = [
        'now_showing' => ['success', 'Đang chiếu'],
        'coming_soon' => ['warning', 'Sắp chiếu'],
        'ended' => ['secondary', 'Ngừng chiếu'],
        'confirmed' => ['success', 'Đã xác nhận'],
        'cancelled' => ['secondary', 'Đã hủy'],
        'admin' => ['danger', 'Quản trị viên'],
        'staff' => ['primary', 'Nhân viên soát vé'],
        'user' => ['info', 'Khách hàng'],
    ];
    [$color, $label] = $map[$status] ?? ['secondary', $status];
@endphp

<span {{ $attributes->merge(['class' => "badge rounded-pill text-bg-{$color}"]) }}>{{ $label }}</span>
