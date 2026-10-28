<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $nowShowing = Movie::with('genres')
            ->where('status', 'now_showing')
            ->withCount(['showtimes as upcoming_count' => fn ($q) => $q->upcoming()])
            ->withAvg(['reviews' => fn ($q) => $q->visible()], 'rating')
            ->orderByDesc('release_date')
            ->take(8)
            ->get();

        $comingSoon = Movie::with('genres')
            ->where('status', 'coming_soon')
            ->orderBy('release_date')
            ->take(4)
            ->get();

        $featured = $nowShowing->first();
        $genres = Genre::withShowingMovies()->orderBy('name')->get();

        // Mục ưu đãi: các chương trình giá vé + mã giảm giá đang áp dụng không có điều kiện đơn tối thiểu
        $programs = PageController::programs();
        $voucher = Voucher::listedForCustomers()
            ->first(fn ($v) => ! $v->starts_at?->isFuture() && ! $v->min_order);

        // Khách có vé xem trong hôm nay: nhắc ở đầu trang để đến rạp mở mã QR nhanh
        $user = $request->user();
        $todayTickets = $user && ! $user->isAdmin() ? $user->todayTickets() : collect();

        return view('home', compact('nowShowing', 'comingSoon', 'featured', 'genres', 'programs', 'voucher', 'todayTickets'));
    }
}
