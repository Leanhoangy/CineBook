<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenreController extends Controller
{
    public function index(Request $request): View
    {
        $genres = Genre::withCount('movies')
            ->search($request->query('q'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.genres.index', compact('genres'));
    }

    public function create(): View
    {
        return view('admin.genres.create', ['genre' => new Genre]);
    }

    public function store(GenreRequest $request): RedirectResponse
    {
        $genre = Genre::create($request->validated());

        return redirect()->route('admin.genres.index')->with('success', "Đã thêm thể loại \"{$genre->name}\".");
    }

    public function edit(Genre $genre): View
    {
        return view('admin.genres.edit', compact('genre'));
    }

    public function update(GenreRequest $request, Genre $genre): RedirectResponse
    {
        $genre->update($request->validated());

        return redirect()->route('admin.genres.index')->with('success', "Đã cập nhật thể loại \"{$genre->name}\".");
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        // Bảng trung gian genre_movie có cascadeOnDelete nên liên kết với phim sẽ tự xóa
        $genre->delete();

        return redirect()->route('admin.genres.index')->with('success', "Đã xóa thể loại \"{$genre->name}\".");
    }
}
