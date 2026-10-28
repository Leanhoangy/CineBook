@props(['booking'])

{{-- Trạng thái thanh toán của vé: đã trả (VNPay / tại quầy), chưa trả, chờ hoàn / đã hoàn tiền --}}
@if ($booking->isRefunded())
    <span {{ $attributes->merge(['class' => 'badge rounded-pill text-bg-info']) }}><i class="bi bi-arrow-counterclockwise me-1"></i>Đã hoàn tiền</span>
@elseif ($booking->needsRefund())
    <span {{ $attributes->merge(['class' => 'badge rounded-pill text-bg-danger']) }}><i class="bi bi-exclamation-circle me-1"></i>Chờ hoàn tiền</span>
@elseif ($booking->isPaid())
    <span {{ $attributes->merge(['class' => 'badge rounded-pill text-bg-success']) }}><i class="bi bi-check2-circle me-1"></i>{{ $booking->payment_label }}</span>
@elseif ($booking->isConfirmed())
    <span {{ $attributes->merge(['class' => 'badge rounded-pill text-bg-warning']) }}><i class="bi bi-hourglass-split me-1"></i>Chưa thanh toán</span>
@endif
