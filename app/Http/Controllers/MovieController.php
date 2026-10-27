<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovieController extends Controller
{
    /** Danh sách phim: tìm kiếm + lọc thể loại/trạng thái + phân trang (giữ từ khóa khi chuyển trang) */
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q'));

        $movies = Movie::with('genres')
            ->withAvg(['reviews' => fn ($q) => $q->visible()], 'rating')
            ->search($keyword)
            ->ofGenre($request->query('the-loai'))
            ->ofStatus($request->query('trang-thai'))
            ->where('status', '!=', 'ended')
            ->orderByRaw("CASE WHEN status = 'now_showing' THEN 0 ELSE 1 END")
            ->orderByDesc('release_date')
            ->paginate(8)
            ->withQueryString();

        $genres = Genre::withShowingMovies()->orderBy('name')->get();

        return view('movies.index', compact('movies', 'genres', 'keyword'));
    }

    public function show(Request $request, Movie $movie): View
    {
        $movie->load('genres');

        // Gom suất chiếu sắp tới theo ngày để hiển thị dạng tab
        $showtimesByDate = $movie->upcomingShowtimes()
            ->with('room')
            ->whereHas('room', fn ($q) => $q->active())
            ->where('start_time', '<', today()->addDays(7))
            ->get()
            ->groupBy(fn ($s) => $s->start_time->toDateString());

        $related = Movie::with('genres')
            ->withAvg(['reviews' => fn ($q) => $q->visible()], 'rating')
            ->whereKeyNot($movie->id)
            ->where('status', '!=', 'ended')
            ->whereHas('genres', fn ($q) => $q->whereIn('genres.id', $movie->genres->pluck('id')))
            ->inRandomOrder()
            ->take(4)
            ->get();

        // Đánh giá: điểm trung bình, phân bố số sao và danh sách bình luận (phân trang riêng)
        $visibleReviews = $movie->reviews()->visible();
        $ratingStats = [
            'average' => round((float) (clone $visibleReviews)->avg('rating'), 1),
            'count' => (clone $visibleReviews)->count(),
            'distribution' => (clone $visibleReviews)->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating'),
        ];
        $reviews = (clone $visibleReviews)->with('user')->latest()
            ->paginate(5, pageName: 'trang-danh-gia')
            ->fragment('danh-gia');

        $user = $request->user();
        $myReview = $user ? $movie->reviews()->where('user_id', $user->id)->first() : null;
        $canReview = $movie->canBeReviewedBy($user);

        return view('movies.show', compact('movie', 'showtimesByDate', 'related', 'ratingStats', 'reviews', 'myReview', 'canReview'));
    }

    /** API gợi ý tìm kiếm trực tiếp (AJAX) */
    public function suggest(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('q'));

        if (mb_strlen($keyword) < 2) {
            return response()->json([]);
        }

        $movies = Movie::search($keyword)
            ->where('status', '!=', 'ended')
            ->take(6)
            ->get(['id', 'title', 'slug', 'poster', 'status', 'duration', 'age_rating']);

        return response()->json($movies->map(fn (Movie $m) => [
            'title' => $m->title,
            'url' => route('movies.show', $m),
            'poster' => $m->poster_url,
            'hue' => $m->poster_hue,
            'meta' => $m->status_label.' · '.$m->duration.' phút · '.$m->age_rating,
        ]));
    }
}
