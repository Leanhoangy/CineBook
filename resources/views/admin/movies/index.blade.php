@extends('layouts.admin')

@section('title', 'Quản lý phim')
@section('subtitle', $movies->total().' phim')

@section('content')
    <div class="ad-card">
        <div class="ad-card-header">
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width: 220px" placeholder="Tên phim, đạo diễn, diễn viên...">
                <select name="genre" class="form-select form-select-sm w-auto">
                    <option value="">Mọi thể loại</option>
                    @foreach ($genres as $genre)
                        <option value="{{ $genre->slug }}" @selected(request('genre') === $genre->slug)>{{ $genre->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select form-select-sm w-auto">
                    <option value="">Mọi trạng thái</option>
                    @foreach (\App\Models\Movie::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-light"><i class="bi bi-funnel me-1"></i>Lọc</button>
                @if (request()->hasAny(['q', 'genre', 'status']))
                    <a href="{{ route('admin.movies.index') }}" class="btn btn-sm btn-link text-muted">Xóa lọc</a>
                @endif
            </form>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.movies.trash') }}" class="btn btn-sm btn-light"><i class="bi bi-trash3 me-1"></i>Thùng rác @if ($trashedCount)<span class="badge text-bg-danger ms-1">{{ $trashedCount }}</span>@endif</a>
                <a href="{{ route('admin.movies.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm phim</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table ad-table table-hover">
                <thead><tr><th>Phim</th><th>Thể loại</th><th>Thời lượng</th><th>Khởi chiếu</th><th>Trạng thái</th><th class="text-center">Suất chiếu</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($movies as $movie)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <x-admin-thumb :movie="$movie" />
                                    <div>
                                        <a href="{{ route('admin.movies.show', $movie) }}" class="fw-semibold text-body text-decoration-none">{{ $movie->title }}</a>
                                        <div class="small text-muted">{{ $movie->director }} · <span class="badge text-bg-dark">{{ $movie->age_rating }}</span></div>
                                    </div>
                                </div>
                            </td>
                            <td class="small">{{ $movie->genres->pluck('name')->join(', ') }}</td>
                            <td class="text-nowrap">{{ $movie->duration }} phút</td>
                            <td class="text-nowrap">{{ $movie->release_date->format('d/m/Y') }}</td>
                            <td><x-status-badge :status="$movie->status" /></td>
                            <td class="text-center">{{ $movie->showtimes_count }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.movies.show', $movie) }}" class="btn btn-sm btn-light btn-icon" title="Chi tiết"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.movies.edit', $movie) }}" class="btn btn-sm btn-light btn-icon" title="Sửa"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.movies.destroy', $movie) }}" class="d-inline" data-confirm="Chuyển phim &quot;{{ $movie->title }}&quot; vào thùng rác?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light btn-icon text-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="ad-empty"><i class="bi bi-camera-reels"></i>Không tìm thấy phim nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movies->hasPages())
            <div class="p-3 border-top">{{ $movies->links() }}</div>
        @endif
    </div>
@endsection
