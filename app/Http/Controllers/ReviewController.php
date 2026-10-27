<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Movie;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    /** Thêm mới hoặc sửa đánh giá của khách cho phim (mỗi khách 1 đánh giá/phim) */
    public function store(ReviewRequest $request, Movie $movie): RedirectResponse
    {
        $review = $movie->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated(),
        );

        $message = $review->wasRecentlyCreated ? 'Cảm ơn bạn đã đánh giá phim!' : 'Đã cập nhật đánh giá của bạn.';

        return redirect()->to(route('movies.show', $movie).'#danh-gia')->with('success', $message);
    }

    public function destroy(Review $review): RedirectResponse
    {
        Gate::authorize('delete', $review);

        $review->delete();

        return redirect()->to(route('movies.show', $review->movie).'#danh-gia')->with('success', 'Đã xóa đánh giá.');
    }
}
