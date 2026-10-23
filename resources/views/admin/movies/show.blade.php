@extends('layouts.admin')

@section('title', $movie->title)
@section('subtitle', 'Chi tiết phim')

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="ad-card p-4">
                @if ($movie->poster_url)
                    <img src="{{ $movie->poster_url }}" alt="" class="w-100 rounded-3 mb-3" style="aspect-ratio: 2/3; object-fit: cover">
                @else
                    <div class="ad-thumb-fallback w-100 h-auto mb-3 rounded-3" style="--h: {{ $movie->poster_hue }}; aspect-ratio: 2/3"><i class="bi bi-film fs-1"></i></div>
                @endif
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted fw-normal">Trạng thái</dt><dd class="col-7"><x-status-badge :status="$movie->status" /></dd>
                    <dt class="col-5 text-muted fw-normal">Thể loại</dt><dd class="col-7">{{ $movie->genres->pluck('name')->join(', ') }}</dd>
                    <dt class="col-5 text-muted fw-normal">Thời lượng</dt><dd class="col-7">{{ $movie->duration_label }}</dd>
                    <dt class="col-5 text-muted fw-normal">Khởi chiếu</dt><dd class="col-7">{{ $movie->release_date->format('d/m/Y') }}</dd>
                    <dt class="col-5 text-muted fw-normal">Phân loại</dt><dd class="col-7">{{ $movie->age_rating }}</dd>
                    <dt class="col-5 text-muted fw-normal">Đạo diễn</dt><dd class="col-7">{{ $movie->director ?: '—' }}</dd>
                    <dt class="col-5 text-muted fw-normal">Diễn viên</dt><dd class="col-7">{{ $movie->cast ?: '—' }}</dd>
                </dl>
                <div class="d-flex gap-2 mt-3">
                    <a href="{{ route('admin.movies.edit', $movie) }}" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-pencil me-1"></i>Sửa</a>
                    <a href="{{ route('movies.show', $movie) }}" target="_blank" class="btn btn-light btn-sm"><i class="bi bi-box-arrow-up-right"></i></a>
                    <form method="POST" action="{{ route('admin.movies.destroy', $movie) }}" data-confirm="Chuyển phim này vào thùng rác?">
                        @csrf @method('DELETE')
                        <button class="btn btn-light btn-sm text-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="ad-card p-4 mb-4">
                <h2 class="h6 fw-bold">Nội dung</h2>
                <p class="mb-0" style="line-height: 1.7">{{ $movie->description }}</p>
            </div>
            <div class="ad-card">
                <div class="ad-card-header">
                    <h2>Suất chiếu của phim</h2>
                    <a href="{{ route('admin.showtimes.create', ['movie_id' => $movie->id]) }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm suất chiếu</a>
                </div>
                <div class="table-responsive">
                    <table class="table ad-table">
                        <thead><tr><th>Thời gian</th><th>Phòng</th><th class="text-end">Giá vé</th><th class="text-center">Đơn</th><th class="text-end">Doanh thu</th></tr></thead>
                        <tbody>
                            @forelse ($showtimes as $st)
                                <tr class="{{ $st->start_time->isPast() ? 'text-muted' : '' }}">
                                    <td>{{ $st->start_time->format('H:i d/m/Y') }}</td>
                                    <td>{{ $st->room->name }}</td>
                                    <td class="text-end">@money($st->price)</td>
                                    <td class="text-center">{{ $st->tickets_sold }}</td>
                                    <td class="text-end fw-semibold">@money($st->revenue ?? 0)</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="ad-empty"><i class="bi bi-calendar-x"></i>Phim chưa có suất chiếu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($showtimes->hasPages())
                    <div class="p-3 border-top">{{ $showtimes->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
