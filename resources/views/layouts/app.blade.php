<!DOCTYPE html>
<html lang="vi" data-bs-theme="dark">
<head>
    @include('partials.head')
    <title>@hasSection('title')@yield('title') – @endif{{ config('app.name') }} · Đặt vé xem phim</title>
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg cb-navbar sticky-top py-2">
        <div class="container">
            <a class="navbar-brand cb-brand" href="{{ route('home') }}"><i class="bi bi-film text-danger me-1"></i>Cine<span>Book</span></a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Mở menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto ms-lg-4 gap-lg-2">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('movies.*') ? 'active' : '' }}" href="{{ route('movies.index') }}">Phim</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('schedule.*') ? 'active' : '' }}" href="{{ route('schedule.index') }}">Lịch chiếu</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('promotions') ? 'active' : '' }}" href="{{ route('promotions') }}">Ưu đãi</a></li>
                    @auth
                        @unless (auth()->user()->isAdmin())
                            <li class="nav-item"><a class="nav-link {{ request()->routeIs('bookings.index', 'bookings.show') ? 'active' : '' }}" href="{{ route('bookings.index') }}">Vé của tôi</a></li>
                        @endunless
                    @endauth
                </ul>

                <form action="{{ route('movies.index') }}" method="GET" class="cb-search my-3 my-lg-0 me-lg-3" role="search" data-live-search="{{ route('movies.suggest') }}">
                    <i class="bi bi-search"></i>
                    <input class="form-control form-control-sm py-2" type="search" name="q" value="{{ request('q') }}" placeholder="Tìm phim, đạo diễn, diễn viên..." autocomplete="off" aria-label="Tìm kiếm phim">
                    <div class="cb-suggest d-none"></div>
                </form>

                @auth
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-body dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            @if (auth()->user()->avatar_url)
                                <img src="{{ auth()->user()->avatar_url }}" alt="" class="cb-avatar">
                            @else
                                <span class="cb-avatar">{{ auth()->user()->initials }}</span>
                            @endif
                            <span class="d-lg-none d-xl-inline small fw-semibold">{{ auth()->user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            @if (auth()->user()->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Trang quản trị</a></li>
                            @else
                                <li><a class="dropdown-item" href="{{ route('bookings.index') }}"><i class="bi bi-ticket-perforated me-2"></i>Vé của tôi</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-circle me-2"></i>Tài khoản</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <div class="d-flex gap-2">
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light rounded-pill px-3">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="btn btn-sm btn-primary rounded-pill px-3">Đăng ký</a>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    @include('partials.flash')

    <main>
        @yield('content')
    </main>

    @php $contact = config('cinema.contact'); @endphp
    <footer class="cb-footer mt-5">
        <div class="container py-5">
            <div class="row gy-4">
                <div class="col-lg-4">
                    <div class="cb-brand mb-2"><i class="bi bi-film text-danger me-1"></i>Cine<span>Book</span></div>
                    <p class="small mb-3">Đặt vé xem phim trực tuyến: chọn suất chiếu, chọn ghế trên sơ đồ phòng chiếu và nhận vé điện tử chỉ trong vài phút.</p>
                    <a href="{{ route('promotions') }}" class="cb-footer-promo">
                        <i class="bi bi-emoji-laughing"></i>
                        <span><strong>Thứ Ba vui vẻ</strong> – đồng giá @money(config('cinema.pricing.tuesday')) mọi suất</span>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <h2 class="cb-footer-title">Khám phá</h2>
                    <ul class="list-unstyled cb-footer-links">
                        <li><a href="{{ route('movies.index', ['trang-thai' => 'now_showing']) }}">Phim đang chiếu</a></li>
                        <li><a href="{{ route('movies.index', ['trang-thai' => 'coming_soon']) }}">Phim sắp chiếu</a></li>
                        <li><a href="{{ route('schedule.index') }}">Lịch chiếu</a></li>
                        <li><a href="{{ route('promotions') }}">Ưu đãi & giá vé</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <h2 class="cb-footer-title">Hỗ trợ</h2>
                    <ul class="list-unstyled cb-footer-links">
                        <li><a href="{{ route('support') }}">Câu hỏi thường gặp</a></li>
                        <li><a href="{{ route('promotions') }}#bang-gia">Bảng giá vé</a></li>
                        @auth
                            <li><a href="{{ route('profile.edit') }}">Tài khoản</a></li>
                        @else
                            <li><a href="{{ route('login') }}">Đăng nhập / Đăng ký</a></li>
                            <li><a href="{{ route('password.request') }}">Quên mật khẩu</a></li>
                        @endauth
                    </ul>
                </div>
                <div class="col-md-6 col-lg-4">
                    <h2 class="cb-footer-title">Liên hệ</h2>
                    <ul class="list-unstyled cb-contact small mb-0">
                        <li><i class="bi bi-geo-alt"></i><span>{{ $contact['address'] }}</span></li>
                        <li><i class="bi bi-telephone"></i><span>Hotline {{ $contact['hotline'] }}</span></li>
                        <li><i class="bi bi-envelope"></i><span>{{ $contact['email'] }}</span></li>
                        <li><i class="bi bi-clock"></i><span>{{ $contact['hours'] }}</span></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="cb-footer-bottom">
            <div class="container d-flex flex-wrap justify-content-between gap-2 py-3 small">
                <span>&copy; {{ date('Y') }} CineBook. Đồ án môn Lập trình mã nguồn mở, xây dựng bằng Laravel.</span>
                <span>Thanh toán online qua VNPay hoặc tại quầy</span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    <script src="{{ asset('js/live-search.js') }}?v={{ filemtime(public_path('js/live-search.js')) }}"></script>
    @stack('scripts')
</body>
</html>
