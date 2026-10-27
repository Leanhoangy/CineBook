@extends('layouts.admin')

@section('title', 'Đánh giá phim')
@section('subtitle', $reviews->total().' đánh giá')

@section('content')
    <div class="ad-card">
        <div class="ad-card-header">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width: 230px" placeholder="Nội dung, khách hàng, tên phim...">
                <select name="rating" class="form-select form-select-sm w-auto">
                    <option value="">Mọi số sao</option>
                    @foreach (range(5, 1) as $star)
                        <option value="{{ $star }}" @selected(request('rating') == $star)>{{ $star }} sao</option>
                    @endforeach
                </select>
                <select name="visibility" class="form-select form-select-sm w-auto">
                    <option value="">Mọi trạng thái</option>
                    <option value="visible" @selected(request('visibility') === 'visible')>Đang hiển thị</option>
                    <option value="hidden" @selected(request('visibility') === 'hidden')>Đã ẩn</option>
                </select>
                <button class="btn btn-sm btn-light"><i class="bi bi-funnel me-1"></i>Lọc</button>
                @if (request()->hasAny(['q', 'rating', 'visibility']))
                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-sm btn-link text-muted">Xóa lọc</a>
                @endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table ad-table table-hover">
                <thead><tr><th>Khách hàng</th><th>Phim</th><th>Số sao</th><th style="min-width: 260px">Nội dung</th><th>Trạng thái</th><th>Ngày</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($reviews as $review)
                        <tr class="{{ $review->is_hidden ? 'opacity-50' : '' }}">
                            <td>
                                <div>{{ $review->user->name }}</div>
                                <small class="text-muted">{{ $review->user->email }}</small>
                            </td>
                            <td><a href="{{ route('movies.show', $review->movie) }}" target="_blank" class="text-decoration-none">{{ $review->movie->title }}</a></td>
                            <td class="text-nowrap text-warning">@for ($i = 0; $i < $review->rating; $i++)<i class="bi bi-star-fill"></i>@endfor</td>
                            <td class="small">{{ $review->content ?: '—' }}</td>
                            <td>
                                @if ($review->is_hidden)
                                    <span class="badge rounded-pill text-bg-secondary">Đã ẩn</span>
                                @else
                                    <span class="badge rounded-pill text-bg-success">Hiển thị</span>
                                @endif
                            </td>
                            <td class="small text-nowrap">{{ $review->created_at->format('d/m/Y') }}</td>
                            <td class="text-end text-nowrap">
                                <form method="POST" action="{{ route('admin.reviews.update', $review) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-light btn-icon" title="{{ $review->is_hidden ? 'Hiện lại' : 'Ẩn' }}"><i class="bi {{ $review->is_hidden ? 'bi-eye' : 'bi-eye-slash' }}"></i></button>
                                </form>
                                <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" class="d-inline" data-confirm="Xóa vĩnh viễn đánh giá của {{ $review->user->name }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light btn-icon text-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="ad-empty"><i class="bi bi-chat-square-text"></i>Không tìm thấy đánh giá nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reviews->hasPages())
            <div class="p-3 border-top">{{ $reviews->links() }}</div>
        @endif
    </div>
@endsection
