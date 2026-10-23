<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShowtimeRequest;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function index(Request $request): View
    {
        $showtimes = Showtime::with(['movie', 'room'])
            ->withCount(['confirmedBookings as bookings_count'])
            ->when($request->query('q'), fn ($q, $k) => $q->whereHas('movie', fn ($m) => $m->where('title', 'like', "%{$k}%")))
            ->when($request->query('room'), fn ($q, $room) => $q->where('room_id', $room))
            ->when($request->query('date'), fn ($q, $date) => $q->whereDate('start_time', $date))
            ->when(! $request->query('date') && ! $request->boolean('past'), fn ($q) => $q->where('start_time', '>=', today()))
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        $rooms = Room::orderBy('name')->get();

        return view('admin.showtimes.index', compact('showtimes', 'rooms'));
    }

    public function create(Request $request): View
    {
        return view('admin.showtimes.create', [
            'showtime' => new Showtime([
                'movie_id' => $request->query('movie_id'),
                'price' => 75000,
            ]),
            ...$this->formOptions(),
        ]);
    }

    public function store(ShowtimeRequest $request): RedirectResponse
    {
        $showtime = Showtime::create($this->payload($request));

        return redirect()->route('admin.showtimes.index', ['date' => $showtime->start_time->toDateString()])
            ->with('success', 'Đã thêm suất chiếu "'.$showtime->movie->title.'" lúc '.$showtime->start_time->format('H:i d/m/Y').'.');
    }

    public function edit(Showtime $showtime): View|RedirectResponse
    {
        if ($redirect = $this->guardLocked($showtime)) {
            return $redirect;
        }

        return view('admin.showtimes.edit', ['showtime' => $showtime, ...$this->formOptions()]);
    }

    public function update(ShowtimeRequest $request, Showtime $showtime): RedirectResponse
    {
        if ($redirect = $this->guardLocked($showtime)) {
            return $redirect;
        }

        $showtime->update($this->payload($request));

        return redirect()->route('admin.showtimes.index', ['date' => $showtime->start_time->toDateString()])
            ->with('success', 'Đã cập nhật suất chiếu.');
    }

    public function destroy(Showtime $showtime): RedirectResponse
    {
        if ($showtime->bookings()->exists()) {
            return back()->with('error', 'Không thể xóa suất chiếu đã có khách đặt vé.');
        }

        $showtime->delete();

        return back()->with('success', 'Đã xóa suất chiếu.');
    }

    private function payload(ShowtimeRequest $request): array
    {
        $movie = Movie::findOrFail($request->validated('movie_id'));

        return [
            ...$request->validated(),
            'end_time' => Showtime::calculateEndTime($request->date('start_time'), $movie),
        ];
    }

    private function formOptions(): array
    {
        return [
            'movies' => Movie::whereIn('status', ['now_showing', 'coming_soon'])->orderBy('title')->get(),
            'rooms' => Room::active()->orderBy('name')->get(),
        ];
    }

    /** Suất đã có vé hoặc đã chiếu thì không được sửa */
    private function guardLocked(Showtime $showtime): ?RedirectResponse
    {
        if ($showtime->start_time->isPast()) {
            return redirect()->route('admin.showtimes.index')->with('error', 'Không thể sửa suất chiếu đã diễn ra.');
        }

        if ($showtime->confirmedBookings()->exists()) {
            return redirect()->route('admin.showtimes.index')->with('error', 'Suất chiếu đã có khách đặt vé nên không thể sửa.');
        }

        return null;
    }
}
