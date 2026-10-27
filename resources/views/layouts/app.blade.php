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

    <footer class="cb-footer mt-5 py-4">
        <div class="container">
            <div class="row gy-3 align-items-center">
                <div class="col-md-6">
                    <div class="cb-brand mb-1"><i class="bi bi-film text-danger me-1"></i>Cine<span>Book</span></div>
                    <small>Đồ án môn Lập trình mã nguồn mở – Website đặt vé xem phim xây dựng bằng Laravel.</small>
                </div>
                <div class="col-md-6 text-md-end small">
                    <a href="{{ route('movies.index') }}" class="me-3">Phim</a>
                    <a href="{{ route('schedule.index') }}" class="me-3">Lịch chiếu</a>
                    <span>&copy; {{ date('Y') }} CineBook</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    <script src="{{ asset('js/live-search.js') }}?v={{ filemtime(public_path('js/live-search.js')) }}"></script>
    @stack('scripts')
</body>
</html>
