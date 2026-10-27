@props(['booking'])

{{-- Trạng thái thanh toán của vé: đã trả (VNPay / tại quầy) hoặc chưa trả --}}
@if ($booking->isPaid())
    <span {{ $attributes->merge(['class' => 'badge rounded-pill text-bg-success']) }}><i class="bi bi-check2-circle me-1"></i>{{ $booking->payment_label }}</span>
@elseif ($booking->isConfirmed())
    <span {{ $attributes->merge(['class' => 'badge rounded-pill text-bg-warning']) }}><i class="bi bi-hourglass-split me-1"></i>Chưa thanh toán</span>
@endif
