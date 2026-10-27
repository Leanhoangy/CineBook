@props(['program'])

<a href="{{ $program['url'] }}" class="cb-card cb-promo h-100">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="icon"><i class="bi {{ $program['icon'] }}"></i></div>
        <span class="badge rounded-pill text-bg-dark border">{{ $program['tag'] }}</span>
    </div>
    <h3 class="h5 fw-bold">{{ $program['title'] }}</h3>
    <p class="text-muted small mb-0">{{ $program['text'] }}</p>
</a>
