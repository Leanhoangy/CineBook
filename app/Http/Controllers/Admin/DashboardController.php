<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $confirmed = Booking::where('status', Booking::STATUS_CONFIRMED);

        $stats = [
            'revenue_month' => (clone $confirmed)->where('created_at', '>=', now()->startOfMonth())->sum('total_price'),
            'tickets_today' => BookingSeat::whereHas('booking', fn ($q) => $q
                ->where('status', Booking::STATUS_CONFIRMED)
                ->whereDate('created_at', today()))->count(),
            'movies_showing' => Movie::where('status', 'now_showing')->count(),
            'customers' => User::where('role', User::ROLE_USER)->count(),
            'showtimes_today' => Showtime::whereDate('start_time', today())->count(),
        ];

        // Doanh thu 14 ngày gần nhất (biểu đồ cột)
        $daily = (clone $confirmed)
            ->where('created_at', '>=', today()->subDays(13))
            ->selectRaw('DATE(created_at) as day, SUM(total_price) as revenue, COUNT(*) as orders')
            ->groupBy('day')
            ->pluck('revenue', 'day');

        $chart = collect(range(13, 0))->map(fn ($i) => today()->subDays($i))->map(fn ($d) => [
            'label' => $d->format('d/m'),
            'value' => (int) ($daily[$d->toDateString()] ?? 0),
        ]);

        // Top phim theo doanh thu
        $topMovies = Movie::withTrashed()
            ->join('showtimes', 'showtimes.movie_id', '=', 'movies.id')
            ->join('bookings', 'bookings.showtime_id', '=', 'showtimes.id')
            ->where('bookings.status', Booking::STATUS_CONFIRMED)
            ->groupBy('movies.id', 'movies.title', 'movies.slug')
            ->select('movies.id', 'movies.title', 'movies.slug', DB::raw('SUM(bookings.total_price) as revenue'), DB::raw('COUNT(bookings.id) as orders'))
            ->orderByDesc('revenue')
            ->take(5)
            ->get();

        $recentBookings = Booking::with(['user', 'showtime.movie'])->latest()->take(6)->get();

        $pendingRefunds = Booking::pendingRefund()->count();

        return view('admin.dashboard', compact('pendingRefunds', 'stats', 'chart', 'topMovies', 'recentBookings'));
    }
}
