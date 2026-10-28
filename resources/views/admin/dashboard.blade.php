@extends('layouts.admin')

@section('title', 'Tổng quan')
@section('subtitle', 'Hôm nay, '.now()->translatedFormat('l d/m/Y'))

@section('content')
    @php
        // Thẻ nhỏ nên doanh thu hiện dạng rút gọn (3,79 tỷ ₫), rê chuột để xem số đầy đủ
        $revenue = $stats['revenue_month'];
        $revenueShort = match (true) {
            $revenue >= 1e9 => number_format($revenue / 1e9, 2, ',', '.').' tỷ ₫',
            $revenue >= 1e6 => number_format($revenue / 1e6, 1, ',', '.').' tr ₫',
            default => number_format($revenue, 0, ',', '.').' ₫',
        };
    @endphp
    <div class="row g-3 mb-4">
        @foreach ([
            ['Doanh thu tháng này', $revenueShort, 'bi-cash-stack', '#e8f7ef', '#1a9a5b', number_format($revenue, 0, ',', '.').' ₫'],
            ['Vé bán hôm nay', $stats['tickets_today'], 'bi-ticket-perforated', '#fff0f1', '#ff4d5a', null],
            ['Suất chiếu hôm nay', $stats['showtimes_today'], 'bi-calendar3', '#eef4ff', '#3a6ff7', null],
            ['Phim đang chiếu', $stats['movies_showing'], 'bi-camera-reels', '#fff6e5', '#e39b0b', null],
            ['Khách hàng', $stats['customers'], 'bi-people', '#f3eefe', '#7b4ae2', null],
        ] as [$label, $value, $icon, $bg, $color, $title])
            <div class="{{ $loop->first ? 'col-12' : 'col-6' }} col-md-4 col-xl">
                <div class="ad-card ad-stat h-100">
                    <div class="icon" style="background: {{ $bg }}; color: {{ $color }}"><i class="bi {{ $icon }}"></i></div>
                    <div class="min-w-0">
                        <div class="value text-truncate" @if ($title) title="{{ $title }}" @endif>{{ $value }}</div>
                        <div class="label">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="ad-card h-100">
                <div class="ad-card-header"><h2>Doanh thu 14 ngày gần nhất</h2></div>
                <div class="p-3"><canvas id="revenueChart" height="110" aria-label="Biểu đồ doanh thu"></canvas></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="ad-card h-100">
                <div class="ad-card-header"><h2>Top phim doanh thu cao</h2></div>
                <ul class="list-group list-group-flush">
                    @forelse ($topMovies as $i => $movie)
                        <li class="list-group-item d-flex align-items-center gap-3 py-3">
                            <span class="fw-bold text-muted" style="width: 18px">{{ $i + 1 }}</span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate">{{ $movie->title }}</div>
                                <small class="text-muted">{{ $movie->orders }} đơn đặt vé</small>
                            </div>
                            <strong class="text-nowrap">@money($movie->revenue)</strong>
                        </li>
                    @empty
                        <li class="list-group-item ad-empty"><i class="bi bi-bar-chart"></i>Chưa có dữ liệu</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="ad-card">
        <div class="ad-card-header">
            <h2>Đơn đặt vé mới nhất</h2>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-light">Xem tất cả</a>
        </div>
        <div class="table-responsive">
            <table class="table ad-table table-hover">
                <thead><tr><th>Mã vé</th><th>Khách hàng</th><th>Phim</th><th>Suất chiếu</th><th class="text-end">Tổng tiền</th><th>Trạng thái</th></tr></thead>
                <tbody>
                    @foreach ($recentBookings as $booking)
                        <tr>
                            <td><a href="{{ route('admin.bookings.show', $booking) }}" class="fw-semibold font-monospace">{{ $booking->code }}</a></td>
                            <td>{{ $booking->user->name }}</td>
                            <td>{{ $booking->showtime->movie->title }}</td>
                            <td>{{ $booking->showtime->start_time->format('H:i d/m') }}</td>
                            <td class="text-end fw-semibold">@money($booking->total_price)</td>
                            <td><x-status-badge :status="$booking->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const chartData = @json($chart);
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: chartData.map(d => d.label),
                datasets: [{
                    label: 'Doanh thu',
                    data: chartData.map(d => d.value),
                    backgroundColor: '#ff4d5a',
                    borderRadius: 6,
                    maxBarThickness: 36,
                }],
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (c) => c.parsed.y.toLocaleString('vi-VN') + ' ₫' } },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: (v) => (v / 1000000).toLocaleString('vi-VN') + 'tr' }, grid: { color: '#eef0f4' } },
                    x: { grid: { display: false } },
                },
            },
        });
    </script>
@endpush
