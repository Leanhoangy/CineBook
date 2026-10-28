<li class="list-group-item d-flex gap-3 align-items-center">
    <span class="fw-bold text-success">{{ $booking->checked_in_at->format('H:i') }}</span>
    <div class="flex-grow-1 min-w-0">
        <div class="fw-semibold text-truncate">{{ $booking->showtime->movie->title }}</div>
        <div class="small text-muted">
            <span class="font-monospace">{{ $booking->code }}</span> · {{ $booking->showtime->room->name }}
            · suất {{ $booking->showtime->start_time->format('H:i') }} · ghế {{ $booking->seat_list }}
        </div>
    </div>
</li>
