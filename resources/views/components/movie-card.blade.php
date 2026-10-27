@props(['movie'])

<a href="{{ route('movies.show', $movie) }}" class="cb-movie-card">
    <x-poster :movie="$movie">
        <div class="overlay">
            <span class="btn btn-primary btn-sm rounded-pill px-3">
                <i class="bi bi-ticket-perforated me-1"></i>{{ $movie->status === 'now_showing' ? 'Đặt vé' : 'Xem chi tiết' }}
            </span>
        </div>
    </x-poster>
    <h3>{{ $movie->title }}</h3>
    <div class="cb-movie-meta">
        <i class="bi bi-clock"></i> {{ $movie->duration }} phút
        @if ($movie->reviews_avg_rating)
            · <span class="cb-rating-inline"><i class="bi bi-star-fill"></i> {{ number_format($movie->reviews_avg_rating, 1, ',') }}</span>
        @endif
        @if ($movie->status === 'coming_soon')
            · <i class="bi bi-calendar-event"></i> {{ $movie->release_date->format('d/m/Y') }}
        @endif
    </div>
    <div class="cb-movie-meta text-truncate">{{ $movie->genres->pluck('name')->join(', ') }}</div>
</a>
