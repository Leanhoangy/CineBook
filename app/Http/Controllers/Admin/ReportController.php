<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use App\Services\SalesReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Thống kê kinh doanh theo khoảng ngày chiếu */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ], ['to.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.']);

        // Mặc định: từ đầu tháng đến hôm nay; tối đa 1 năm cho nhẹ truy vấn
        $to = $request->date('to') ?? today();
        $from = $request->date('from') ?? today()->startOfMonth();
        $from = max($from, $to->copy()->subYear());

        $report = new SalesReport($from, $to);

        $presets = [
            'Hôm nay' => [today(), today()],
            '7 ngày' => [today()->subDays(6), today()],
            '30 ngày' => [today()->subDays(29), today()],
            'Tháng này' => [today()->startOfMonth(), today()],
            'Tháng trước' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
        ];

        // Khoảng ngày chọn bắt đầu trước suất chiếu đầu tiên trong hệ thống: báo để không tưởng là mất doanh thu
        $firstShowtime = Showtime::min('start_time');
        $dataStartsAt = $firstShowtime && $from->lt(Carbon::parse($firstShowtime)->startOfDay()) ? Carbon::parse($firstShowtime) : null;

        return view('admin.reports.index', [
            'dataStartsAt' => $dataStartsAt,
            'from' => $from,
            'to' => $to,
            'presets' => $presets,
            'totals' => $report->totals(),
            'daily' => $report->daily(),
            'slots' => $report->bySlot(),
            'movies' => $report->byMovie(),
            'combos' => $report->combos(),
        ]);
    }
}
