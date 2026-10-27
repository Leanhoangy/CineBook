@extends('layouts.app')

@section('title', 'Danh sách phim')

@section('content')
    <div class="container py-4 py-lg-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h1 class="cb-section-title mb-1">Danh sách phim</h1>
                <p class="text-muted mb-0">
                    @if ($keyword)
                        Tìm thấy <strong class="text-body">{{ $movies->total() }}</strong> phim cho từ khóa “{{ $keyword }}”
                    @else
                        {{ $movies->total() }} phim đang chiếu và sắp chiếu
                    @endif
                </p>
            </div>

            {{-- Form tìm kiếm + lọc: dùng GET để giữ điều kiện trên URL khi phân trang --}}
            <form method="GET" action="{{ route('movies.index') }}" class="d-flex flex-wrap gap-2">
                <input type="search" name="q" value="{{ $keyword }}" class="form-control" placeholder="Tên phim, đạo diễn, diễn viên..." style="min-width: 240px">
                <select name="trang-thai" class="form-select w-auto" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="now_showing" @selected(request('trang-thai') === 'now_showing')>Đang chiếu</option>
                    <option value="coming_soon" @selected(request('trang-thai') === 'coming_soon')>Sắp chiếu</option>
                </select>
                @if (request('the-loai'))
                    <input type="hidden" name="the-loai" value="{{ request('the-loai') }}">
                @endif
                <button class="btn btn-primary"><i class="bi bi-search"></i><span class="d-none d-sm-inline ms-1">Tìm</span></button>
            </form>
        </div>

        <div class="d-flex gap-2 overflow-auto pb-2 mb-4" data-scroll-active>
            <a href="{{ route('movies.index', request()->except(['the-loai', 'page'])) }}" class="cb-chip {{ request('the-loai') ? '' : 'active' }}">Tất cả thể loại</a>
            @foreach ($genres as $genre)
                <a href="{{ route('movies.index', [...request()->except('page'), 'the-loai' => $genre->slug]) }}"
                   class="cb-chip {{ request('the-loai') === $genre->slug ? 'active' : '' }}">{{ $genre->name }}</a>
            @endforeach
        </div>

        @if ($movies->isEmpty())
            <div class="cb-card cb-empty">
                <i class="bi bi-search"></i>
                <p class="mb-3">Không tìm thấy phim phù hợp.</p>
                <a href="{{ route('movies.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">Xóa bộ lọc</a>
            </div>
        @else
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-lg-4">
                @foreach ($movies as $movie)
                    <div class="col"><x-movie-card :movie="$movie" /></div>
                @endforeach
            </div>

            <div class="d-flex justify-content-center mt-5">
                {{ $movies->links() }}
            </div>
        @endif
    </div>
@endsection
