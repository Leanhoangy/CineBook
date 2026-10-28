<!DOCTYPE html>
<html lang="vi">
<head>
    @include('partials.head')
    <title>@yield('title', 'Quản trị') – {{ config('app.name') }} Admin</title>
    <link href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    @php
        $menu = [
            ['admin.dashboard', 'admin.dashboard', 'bi-speedometer2', 'Tổng quan'],
            'Nội dung',
            ['admin.movies.index', 'admin.movies.*', 'bi-camera-reels', 'Phim'],
            ['admin.genres.index', 'admin.genres.*', 'bi-tags', 'Thể loại'],
            ['admin.rooms.index', 'admin.rooms.*', 'bi-door-open', 'Phòng chiếu'],
            ['admin.showtimes.index', 'admin.showtimes.*', 'bi-calendar3', 'Suất chiếu'],
            ['admin.reviews.index', 'admin.reviews.*', 'bi-chat-square-text', 'Đánh giá'],
            'Kinh doanh',
            ['admin.check-in.index', 'admin.check-in.*', 'bi-qr-code-scan', 'Soát vé', $checkInOpenCount],
            ['admin.bookings.index', 'admin.bookings.*', 'bi-receipt', 'Đơn đặt vé'],
            ['admin.vouchers.index', 'admin.vouchers.*', 'bi-ticket-detailed', 'Mã giảm giá'],
            ['admin.combos.index', 'admin.combos.*', 'bi-cup-straw', 'Bắp nước'],
            ['admin.users.index', 'admin.users.*', 'bi-people', 'Người dùng'],
        ];
    @endphp

    <aside class="ad-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar">
        <div class="p-3 d-flex align-items-center justify-content-between">
            <a href="{{ route('admin.dashboard') }}" class="ad-brand"><i class="bi bi-film me-1" style="color: var(--ad-accent)"></i>Cine<span>Book</span><small>Khu vực quản trị</small></a>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Đóng"></button>
        </div>
        <nav class="ad-nav nav flex-column px-3 pb-4">
            @foreach ($menu as $item)
                @if (is_string($item))
                    <div class="ad-nav-label">{{ $item }}</div>
                @else
                    <a href="{{ route($item[0]) }}" class="nav-link {{ request()->routeIs($item[1]) ? 'active' : '' }}">
                        <i class="bi {{ $item[2] }}"></i>{{ $item[3] }}
                        @if (! empty($item[4]))<span class="badge rounded-pill text-bg-danger" title="{{ $item[4] }} suất đang trong giờ soát vé">{{ $item[4] }}</span>@endif
                    </a>
                @endif
            @endforeach
            <div class="ad-nav-label">Khác</div>
            <a href="{{ route('home') }}" class="nav-link" target="_blank"><i class="bi bi-box-arrow-up-right"></i>Xem trang khách</a>
        </nav>
    </aside>

    <div class="ad-main">
        <header class="ad-topbar px-3 px-lg-4 py-2 d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-label="Mở menu">
                <i class="bi bi-list"></i>
            </button>
            <div class="flex-grow-1">
                <h1 class="h5 fw-bold mb-0">@yield('title', 'Quản trị')</h1>
                @hasSection('subtitle')
                    <small class="text-muted">@yield('subtitle')</small>
                @endif
            </div>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-body dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    @if (auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="" class="ad-avatar">
                    @else
                        <span class="ad-avatar">{{ auth()->user()->initials }}</span>
                    @endif
                    <span class="d-none d-sm-inline small fw-semibold">{{ auth()->user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
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
        </header>

        @include('partials.flash')

        <main class="p-3 p-lg-4">
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @stack('scripts')
</body>
</html>
