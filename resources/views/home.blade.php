@extends('layouts.app')

@section('content')
    {{-- Hero: phim nổi bật --}}
    <section class="cb-hero py-5" @if ($featured) style="--h: {{ $featured->poster_hue }}" @endif>
        <div class="container py-lg-4">
            <div class="row align-items-center gy-4">
                <div class="col-lg-7">
                    <span class="badge rounded-pill text-bg-danger mb-3 px-3 py-2"><i class="bi bi-fire me-1"></i>Đang hot tại CineBook</span>
                    @if ($featured)
                        <h1 class="mb-3">{{ $featured->title }}</h1>
                        <p class="text-muted mb-2">
                            <span class="me-3"><i class="bi bi-clock me-1"></i>{{ $featured->duration_label }}</span>
                            <span class="me-3"><i class="bi bi-tags me-1"></i>{{ $featured->genres->pluck('name')->join(', ') }}</span>
                            <span class="cb-age position-static {{ $featured->age_rating }}">{{ $featured->age_rating }}</span>
                        </p>
                        <p class="lead mb-4">{{ \Illuminate\Support\Str::limit($featured->description, 180) }}</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('movies.show', $featured) }}" class="btn btn-primary btn-lg rounded-pill px-4"><i class="bi bi-ticket-perforated me-2"></i>Đặt vé ngay</a>
                            <a href="{{ route('schedule.index') }}" class="btn btn-outline-light btn-lg rounded-pill px-4">Xem lịch chiếu</a>
                        </div>
                    @else
                        <h1 class="mb-3">Đặt vé xem phim<br>nhanh chóng, dễ dàng</h1>
                        <p class="lead">Chọn phim, chọn suất, chọn ghế – chỉ trong vài cú nhấp chuột.</p>
                    @endif
                </div>
                @if ($featured)
                    <div class="col-lg-5 d-none d-lg-block">
                        <x-poster :movie="$featured" />
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="container">
        {{-- Thể loại --}}
        <section class="mt-5">
            <div class="d-flex gap-2 overflow-auto pb-2">
                <a href="{{ route('movies.index') }}" class="cb-chip active"><i class="bi bi-grid"></i>Tất cả</a>
                @foreach ($genres as $genre)
                    <a href="{{ route('movies.index', ['the-loai' => $genre->slug]) }}" class="cb-chip">{{ $genre->name }} <small>{{ $genre->movies_count }}</small></a>
                @endforeach
            </div>
        </section>

        {{-- Phim đang chiếu --}}
        <section class="mt-4">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <h2 class="cb-section-title mb-0">Phim đang chiếu</h2>
                <a href="{{ route('movies.index', ['trang-thai' => 'now_showing']) }}" class="small">Xem tất cả <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-lg-4">
                @forelse ($nowShowing as $movie)
                    <div class="col"><x-movie-card :movie="$movie" /></div>
                @empty
                    <div class="col-12 cb-empty"><i class="bi bi-camera-reels"></i>Chưa có phim đang chiếu.</div>
                @endforelse
            </div>
        </section>

        {{-- Phim sắp chiếu --}}
        @if ($comingSoon->isNotEmpty())
            <section class="mt-5">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <h2 class="cb-section-title mb-0">Sắp khởi chiếu</h2>
                    <a href="{{ route('movies.index', ['trang-thai' => 'coming_soon']) }}" class="small">Xem tất cả <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-lg-4">
                    @foreach ($comingSoon as $movie)
                        <div class="col"><x-movie-card :movie="$movie" /></div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Ưu đãi --}}
        <section class="mt-5">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <h2 class="cb-section-title mb-0">Ưu đãi</h2>
                <a href="{{ route('promotions') }}" class="small">Xem tất cả <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="row g-3">
                @foreach ($programs as $program)
                    <div class="col-md-6 col-lg-3"><x-promo-card :program="$program" /></div>
                @endforeach
                @if ($voucher)
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('promotions') }}#ma-giam-gia" class="cb-card cb-promo cb-promo-code h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="icon"><i class="bi bi-ticket-perforated"></i></div>
                                <span class="badge rounded-pill text-bg-dark border">Mã giảm giá</span>
                            </div>
                            <h3 class="h5 fw-bold">{{ $voucher->label }}</h3>
                            <p class="text-muted small mb-2">{{ $voucher->description }}</p>
                            <code class="cb-voucher-code">{{ $voucher->code }}</code>
                        </a>
                    </div>
                @endif
            </div>
        </section>

        {{-- Lợi ích --}}
        <section class="mt-5">
            <div class="row g-3">
                @foreach ([
                    ['bi-lightning-charge', 'Đặt vé siêu tốc', 'Chọn suất chiếu và ghế ngồi trực tiếp trên sơ đồ phòng chiếu.'],
                    ['bi-grid-3x3-gap', 'Sơ đồ ghế thời gian thực', 'Ghế vừa có người đặt sẽ được cập nhật tự động, không lo trùng ghế.'],
                    ['bi-shield-check', 'Quản lý vé dễ dàng', 'Xem lại lịch sử, mã vé và hủy vé trước giờ chiếu ngay trên website.'],
                ] as [$icon, $title, $text])
                    <div class="col-md-4">
                        <div class="cb-card cb-feature">
                            <div class="icon"><i class="bi {{ $icon }}"></i></div>
                            <h3 class="h6 fw-bold">{{ $title }}</h3>
                            <p class="text-muted small mb-0">{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
