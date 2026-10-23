@extends('layouts.admin')

@section('title', 'Thể loại phim')

@section('content')
    <div class="ad-card">
        <div class="ad-card-header">
            <form method="GET" class="d-flex gap-2">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm thể loại...">
                <button class="btn btn-sm btn-light"><i class="bi bi-search"></i></button>
            </form>
            <a href="{{ route('admin.genres.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm thể loại</a>
        </div>
        <div class="table-responsive">
            <table class="table ad-table table-hover">
                <thead><tr><th>#</th><th>Tên thể loại</th><th>Slug</th><th>Mô tả</th><th class="text-center">Số phim</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($genres as $genre)
                        <tr>
                            <td class="text-muted">{{ $genres->firstItem() + $loop->index }}</td>
                            <td class="fw-semibold">{{ $genre->name }}</td>
                            <td><code>{{ $genre->slug }}</code></td>
                            <td class="text-muted small">{{ $genre->description }}</td>
                            <td class="text-center"><a href="{{ route('admin.movies.index', ['genre' => $genre->slug]) }}" class="badge text-bg-light text-decoration-none">{{ $genre->movies_count }}</a></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.genres.edit', $genre) }}" class="btn btn-sm btn-light btn-icon" title="Sửa"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.genres.destroy', $genre) }}" class="d-inline" data-confirm="Xóa thể loại &quot;{{ $genre->name }}&quot;? Các phim sẽ bị gỡ thể loại này.">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light btn-icon text-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ad-empty"><i class="bi bi-tags"></i>Không có thể loại nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($genres->hasPages())
            <div class="p-3 border-top">{{ $genres->links() }}</div>
        @endif
    </div>
@endsection
