@extends('layouts.admin')

@section('title', 'Thống kê')
@section('subtitle', 'Suất chiếu từ '.$from->format('d/m/Y').' đến '.$to->format('d/m/Y'))

@section('content')
    @php
        // 3.794.577.500 → "3,79 tỷ ₫" cho ô số liệu nhỏ (rê chuột xem số đầy đủ)
        $short = fn (int $v) => match (true) {
            $v >= 1e9 => number_format($v / 1e9, 2, ',', '.').' tỷ ₫',
            $v >= 1e6 => number_format($v / 1e6, 1, ',', '.').' tr ₫',
            default => number_format($v, 0, ',', '.').' ₫',
        };
        $full = fn (int $v) => number_format($v, 0, ',', '.').' ₫';
    @endphp

    <div class="ad-card mb-4">
        <div class="ad-card-header flex-wrap gap-2">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <span class="small text-muted">Từ</span>
                <input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control form-control-sm w-auto @error('from') is-invalid @enderror">
                <span class="small text-muted">đến</span>
                <input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control form-control-sm w-auto @error('to') is-invalid @enderror">
                <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Xem</button>
            </form>
            <div class="d-flex flex-wrap gap-1">
                @foreach ($presets as $label => [$pFrom, $pTo])
                    @php $active = $pFrom->isSameDay($from) && $pTo->isSameDay($to); @endphp
                    <a href="{{ route('admin.reports.index', ['from' => $pFrom->toDateString(), 'to' => $pTo->toDateString()]) }}"
                       class="btn btn-sm {{ $active ? 'btn-dark' : 'btn-light' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        @error('to') <div class="px-3 pb-2 small text-danger">{{ $message }}</div> @enderror
        <div class="px-3 pb-3 small text-muted">
            <i class="bi bi-info-circle me-1"></i>Tính theo <strong>ngày chiếu</strong>, chỉ các suất đã bắt đầu và vé đã xác nhận (không tính vé đã hủy / đã hoàn tiền).
            Tỉ lệ lấp đầy = ghế đã bán / tổng ghế của các suất.
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['Doanh thu', $short($totals['revenue']), $full($totals['revenue']), 'bi-cash-stack', '#e8f7ef', '#1a9a5b'],
            ['Vé bán ra', number_format($totals['seats'], 0, ',', '.'), number_format($totals['orders'], 0, ',', '.').' đơn', 'bi-ticket-perforated', '#fff0f1', '#ff4d5a'],
            ['Tỉ lệ lấp đầy', number_format($totals['occupancy'], 1, ',', '.').'%', number_format($totals['showtimes'], 0, ',', '.').' suất · '.number_format($totals['capacity'], 0, ',', '.').' ghế', 'bi-grid-3x3-gap', '#eef4ff', '#3a6ff7'],
            ['Bắp nước', $short($totals['combo_revenue']), $totals['combo_rate'].'% đơn mua kèm', 'bi-cup-straw', '#fff6e5', '#e39b0b'],
        ] as [$label, $value, $hint, $icon, $bg, $color])
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="ad-card ad-stat h-100">
                    <div class="icon" style="background: {{ $bg }}; color: {{ $color }}"><i class="bi {{ $icon }}"></i></div>
                    <div class="min-w-0">
                        <div class="value text-truncate" title="{{ $hint }}">{{ $value }}</div>
                        <div class="label">{{ $label }}</div>
                        <div class="small text-muted text-truncate">{{ $hint }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="ad-card mb-4">
        <div class="ad-card-header">
            <h2>Doanh thu theo ngày chiếu</h2>
            <span class="small text-muted">Vé {{ $short($totals['ticket_revenue']) }} · Bắp nước {{ $short($totals['combo_revenue']) }}</span>
        </div>
        <div class="p-3">
            @if ($totals['orders'])
                <canvas id="dailyChart" height="90" aria-label="Biểu đồ doanh thu theo ngày chiếu" role="img"></canvas>
            @else
                <div class="ad-empty"><i class="bi bi-bar-chart"></i>Không có suất chiếu nào có vé trong khoảng này.</div>
            @endif
        </div>
    </div>

    <div class="ad-card mb-4">
        <div class="ad-card-header"><h2>Hiệu quả theo khung giờ</h2></div>
        <div class="table-responsive">
            <table class="table ad-table">
                <thead>
                    <tr><th>Khung giờ</th><th class="text-end">Suất</th><th class="text-end">Vé bán</th><th style="min-width: 180px">Lấp đầy</th><th class="text-end">Doanh thu</th><th>Phim bán nhiều vé nhất</th></tr>
                </thead>
                <tbody>
                    @foreach ($slots as $slot)
                        <tr>
                            <td class="fw-semibold text-nowrap">{{ $slot['label'] }}</td>
                            <td class="text-end">{{ $slot['showtimes'] }}</td>
                            <td class="text-end">{{ number_format($slot['seats'], 0, ',', '.') }}</td>
                            <td><x-admin.meter :value="$slot['occupancy']" /></td>
                            <td class="text-end text-nowrap fw-semibold">@money($slot['revenue'])</td>
                            <td class="small" style="min-width: 200px">
                                @if ($slot['top_movie'])
                                    {{ $slot['top_movie']['title'] }} <span class="text-muted">({{ $slot['top_movie']['seats'] }} vé)</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="ad-card h-100">
                <div class="ad-card-header"><h2>Phim doanh thu cao</h2></div>
                <div class="table-responsive">
                    <table class="table ad-table">
                        <thead><tr><th>#</th><th>Phim</th><th class="text-end">Suất</th><th class="text-end">Vé</th><th style="min-width: 160px">Lấp đầy</th><th class="text-end">Doanh thu</th></tr></thead>
                        <tbody>
                            @forelse ($movies as $i => $movie)
                                <tr>
                                    <td class="text-muted">{{ $i + 1 }}</td>
                                    <td class="fw-semibold" style="min-width: 200px">{{ $movie['title'] }}</td>
                                    <td class="text-end">{{ $movie['showtimes'] }}</td>
                                    <td class="text-end">{{ number_format($movie['seats'], 0, ',', '.') }}</td>
                                    <td><x-admin.meter :value="$movie['occupancy']" /></td>
                                    <td class="text-end text-nowrap fw-semibold">@money($movie['revenue'])</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="ad-empty"><i class="bi bi-film"></i>Chưa có dữ liệu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="ad-card h-100">
                <div class="ad-card-header"><h2>Bắp nước bán chạy</h2></div>
                <ul class="list-group list-group-flush">
                    @forelse ($combos as $combo)
                        <li class="list-group-item d-flex align-items-center gap-3 py-3">
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate">{{ $combo->name }}</div>
                                <small class="text-muted">{{ number_format($combo->quantity, 0, ',', '.') }} phần</small>
                            </div>
                            <strong class="text-nowrap">@money($combo->revenue)</strong>
                        </li>
                    @empty
                        <li class="list-group-item ad-empty"><i class="bi bi-cup-straw"></i>Chưa bán bắp nước nào.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection

@if ($totals['orders'])
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            const daily = @json($daily);
            new Chart(document.getElementById('dailyChart'), {
                type: 'bar',
                data: {
                    labels: daily.map((d) => d.label),
                    datasets: [{ label: 'Doanh thu', data: daily.map((d) => d.value), backgroundColor: '#ff4d5a', borderRadius: 4, maxBarThickness: 28 }],
                },
                options: {
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { title: (items) => 'Ngày ' + items[0].label, label: (c) => c.parsed.y.toLocaleString('vi-VN') + ' ₫' } },
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: (v) => (v / 1000000).toLocaleString('vi-VN') + 'tr' }, grid: { color: '#eef0f4' }, border: { display: false } },
                        x: { grid: { display: false }, ticks: { autoSkip: true, maxRotation: 0 } },
                    },
                },
            });
        </script>
    @endpush
@endif
