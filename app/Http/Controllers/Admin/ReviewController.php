<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Kiểm duyệt đánh giá: ẩn/hiện bình luận vi phạm hoặc xóa hẳn */
class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = Review::with(['user', 'movie'])
            ->search($request->query('q'))
            ->when($request->query('rating'), fn ($q, $r) => $q->where('rating', $r))
            ->when($request->query('visibility') === 'hidden', fn ($q) => $q->where('is_hidden', true))
            ->when($request->query('visibility') === 'visible', fn ($q) => $q->where('is_hidden', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function update(Review $review): RedirectResponse
    {
        $review->update(['is_hidden' => ! $review->is_hidden]);

        return back()->with('success', $review->is_hidden
            ? "Đã ẩn đánh giá của {$review->user->name}."
            : "Đã hiện lại đánh giá của {$review->user->name}.");
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', "Đã xóa đánh giá của {$review->user->name}.");
    }
}
