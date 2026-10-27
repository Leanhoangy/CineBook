<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Movie;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
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

        return view('home', compact('nowShowing', 'comingSoon', 'featured', 'genres'));
    }
}
