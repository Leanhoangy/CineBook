@props(['rating'])

{{-- Hiển thị số sao, hỗ trợ nửa sao (VD: 4,5) --}}
<span {{ $attributes->merge(['class' => 'cb-stars']) }} aria-label="{{ $rating }} trên 5 sao">
    @foreach (range(1, 5) as $i)
        @if ($rating >= $i)
            <i class="bi bi-star-fill"></i>
        @elseif ($rating >= $i - 0.5)
            <i class="bi bi-star-half"></i>
        @else
            <i class="bi bi-star"></i>
        @endif
    @endforeach
</span>
