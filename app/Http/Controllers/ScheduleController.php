<?php

namespace App\Http\Controllers;

use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /** Lịch chiếu theo ngày, gom theo phim */
    public function index(Request $request): View
    {
        $days = collect(range(0, 6))->map(fn ($i) => today()->addDays($i));

        try {
            $date = Carbon::parse($request->query('ngay', today()->toDateString()))->startOfDay();
        } catch (\Throwable) {
            $date = today();
        }

        if ($date->lt(today()) || $date->gt(today()->addDays(6))) {
            $date = today();
        }

        $showtimes = Showtime::with(['movie.genres', 'room'])
            ->whereBetween('start_time', [$date->isToday() ? now() : $date, $date->copy()->endOfDay()])
            ->whereHas('movie', fn ($q) => $q->where('status', '!=', 'ended'))
            ->whereHas('room', fn ($q) => $q->active())
            ->orderBy('start_time')
            ->get()
            ->groupBy('movie_id');

        return view('schedule.index', compact('days', 'date', 'showtimes'));
    }
}
