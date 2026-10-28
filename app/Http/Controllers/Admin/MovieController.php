<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MovieRequest;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MovieController extends Controller
{
    public function index(Request $request): View
    {
        $movies = Movie::with('genres')
            ->withCount('showtimes')
            ->search($request->query('q'))
            ->ofGenre($request->query('genre'))
            ->ofStatus($request->query('status'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $genres = Genre::orderBy('name')->get();
        $trashedCount = Movie::onlyTrashed()->count();

        return view('admin.movies.index', compact('movies', 'genres', 'trashedCount'));
    }

    public function create(): View
    {
        return view('admin.movies.create', [
            'movie' => new Movie(['status' => 'coming_soon', 'age_rating' => 'P']),
            'genres' => Genre::orderBy('name')->get(),
        ]);
    }

    public function store(MovieRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['poster', 'genres', 'remove_poster']);

        if ($request->hasFile('poster')) {
            $data['poster'] = $request->file('poster')->store('posters', 'public');
        }

        $movie = DB::transaction(function () use ($data, $request) {
            $movie = Movie::create($data);
            $movie->genres()->sync($request->validated('genres'));

            return $movie;
        });

        return redirect()->route('admin.movies.show', $movie)->with('success', "Đã thêm phim \"{$movie->title}\".");
    }

    public function show(Movie $movie): View
    {
        $movie->load('genres');

        $showtimes = $movie->showtimes()
            ->with('room')
            ->withCount(['bookings as tickets_sold' => fn ($q) => $q->where('status', 'confirmed')])
            ->withSum(['bookings as revenue' => fn ($q) => $q->where('status', 'confirmed')], 'total_price')
            ->orderByDesc('start_time')
            ->paginate(10);

        return view('admin.movies.show', compact('movie', 'showtimes'));
    }

    public function edit(Movie $movie): View
    {
        return view('admin.movies.edit', [
            'movie' => $movie->load('genres'),
            'genres' => Genre::orderBy('name')->get(),
        ]);
    }

    public function update(MovieRequest $request, Movie $movie): RedirectResponse
    {
        $data = $request->safe()->except(['poster', 'genres', 'remove_poster']);

        if ($request->hasFile('poster')) {
            $this->deletePoster($movie);
            $data['poster'] = $request->file('poster')->store('posters', 'public');
        } elseif ($request->boolean('remove_poster')) {
            $this->deletePoster($movie);
            $data['poster'] = null;
        }

        DB::transaction(function () use ($movie, $data, $request) {
            $movie->update($data);
            $movie->genres()->sync($request->validated('genres'));
        });

        return redirect()->route('admin.movies.show', $movie)->with('success', "Đã cập nhật phim \"{$movie->title}\".");
    }

    /** Xóa mềm (Soft Delete): phim được chuyển vào thùng rác, có thể khôi phục */
    public function destroy(Movie $movie): RedirectResponse
    {
        if ($movie->showtimes()->upcoming()->exists()) {
            return back()->with('error', "Phim \"{$movie->title}\" vẫn còn suất chiếu sắp tới, hãy xóa các suất chiếu trước.");
        }

        $movie->delete();

        return redirect()->route('admin.movies.index')->with('success', "Đã chuyển phim \"{$movie->title}\" vào thùng rác.");
    }

    public function trash(): View
    {
        $movies = Movie::onlyTrashed()->withCount('showtimes')->latest('deleted_at')->paginate(10);

        return view('admin.movies.trash', compact('movies'));
    }

    public function restore(Movie $movie): RedirectResponse
    {
        $movie->restore();

        return redirect()->route('admin.movies.trash')->with('success', "Đã khôi phục phim \"{$movie->title}\".");
    }

    public function forceDelete(Movie $movie): RedirectResponse
    {
        if ($movie->showtimes()->withTrashed()->exists()) {
            return back()->with('error', "Không thể xóa vĩnh viễn \"{$movie->title}\" vì phim đã có lịch sử suất chiếu và vé bán ra.");
        }

        $this->deletePoster($movie);
        $movie->forceDelete();

        return redirect()->route('admin.movies.trash')->with('success', "Đã xóa vĩnh viễn phim \"{$movie->title}\".");
    }

    private function deletePoster(Movie $movie): void
    {
        if ($movie->poster && ! str_starts_with($movie->poster, 'http')) {
            Storage::disk('public')->delete($movie->poster);
        }
    }
}
