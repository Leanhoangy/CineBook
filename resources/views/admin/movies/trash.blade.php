@extends('layouts.admin')

@section('title', 'Thùng rác phim')
@section('subtitle', 'Phim đã xóa mềm (Soft Delete) có thể khôi phục')

@section('content')
    <a href="{{ route('admin.movies.index') }}" class="btn btn-sm btn-light mb-3"><i class="bi bi-arrow-left me-1"></i>Danh sách phim</a>
    <div class="ad-card">
        <div class="table-responsive">
            <table class="table ad-table table-hover">
                <thead><tr><th>Phim</th><th>Ngày xóa</th><th class="text-center">Suất chiếu</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($movies as $movie)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <x-admin-thumb :movie="$movie" />
                                    <span class="fw-semibold">{{ $movie->title }}</span>
                                </div>
                            </td>
                            <td>{{ $movie->deleted_at->format('H:i d/m/Y') }}</td>
                            <td class="text-center">{{ $movie->showtimes_count }}</td>
                            <td class="text-end text-nowrap">
                                <form method="POST" action="{{ route('admin.movies.restore', $movie) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-light"><i class="bi bi-arrow-counterclockwise me-1"></i>Khôi phục</button>
                                </form>
                                <form method="POST" action="{{ route('admin.movies.force-delete', $movie) }}" class="d-inline" data-confirm="Xóa vĩnh viễn phim &quot;{{ $movie->title }}&quot;? Không thể hoàn tác.">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light text-danger"><i class="bi bi-x-octagon me-1"></i>Xóa vĩnh viễn</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="ad-empty"><i class="bi bi-trash3"></i>Thùng rác trống.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movies->hasPages())
            <div class="p-3 border-top">{{ $movies->links() }}</div>
        @endif
    </div>
@endsection
