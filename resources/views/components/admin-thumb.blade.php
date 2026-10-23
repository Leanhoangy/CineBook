@props(['movie'])

@if ($movie->poster_url)
    <img src="{{ $movie->poster_url }}" alt="" class="ad-thumb">
@else
    <span class="ad-thumb-fallback" style="--h: {{ $movie->poster_hue }}"><i class="bi bi-film"></i></span>
@endif
