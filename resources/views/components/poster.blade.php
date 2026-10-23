@props(['movie', 'showAge' => true])

<div {{ $attributes->merge(['class' => 'cb-poster']) }}>
    @if ($showAge)
        <span class="cb-age {{ $movie->age_rating }}">{{ $movie->age_rating }}</span>
    @endif
    @if ($movie->poster_url)
        <img src="{{ $movie->poster_url }}" alt="Poster {{ $movie->title }}" loading="lazy">
    @else
        <div class="cb-poster-fallback" style="--h: {{ $movie->poster_hue }}">
            <i class="bi bi-film"></i>
            <div class="title">{{ $movie->title }}</div>
        </div>
    @endif
    {{ $slot }}
</div>
