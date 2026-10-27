<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Voucher;
use Illuminate\View\View;

/** Các trang thông tin: Ưu đãi & bảng giá vé, Hỗ trợ khách hàng */
class PageController extends Controller
{
    public function promotions(): View
    {
        return view('pages.promotions', [
            'programs' => self::programs(),
            'pricing' => config('cinema.pricing'),
            'vouchers' => Voucher::listedForCustomers(),
            'rooms' => Room::active()->get()->sortByDesc('capacity'),
        ]);
    }

    public function support(): View
    {
        return view('pages.support');
    }

    /** Các chương trình giá vé đang áp dụng (lấy số tiền từ bảng giá trong config/cinema.php) */
    public static function programs(): array
    {
        $pricing = config('cinema.pricing');
        $money = fn (int $n) => number_format($n, 0, ',', '.').' ₫';

        return [
            [
                'icon' => 'bi-emoji-laughing', 'tag' => 'Mỗi thứ Ba',
                'title' => 'Thứ Ba vui vẻ',
                'text' => 'Đồng giá '.$money($pricing['tuesday']).' cho ghế thường ở mọi suất chiếu, từ sáng đến khuya.',
                'url' => route('schedule.index', ['ngay' => today()->next('Tuesday')->toDateString()]),
            ],
            [
                'icon' => 'bi-sunrise', 'tag' => 'Trước 12h',
                'title' => 'Suất sáng giá rẻ',
                'text' => 'Chỉ từ '.$money($pricing['morning']).' cho các suất chiếu bắt đầu trước 12 giờ trưa.',
                'url' => route('schedule.index'),
            ],
            [
                'icon' => 'bi-calendar2-check', 'tag' => 'Phim sắp chiếu',
                'title' => 'Đặt vé trước',
                'text' => 'Phim bom tấn mở bán vé trước ngày khởi chiếu: giữ chỗ đẹp sớm, không lo hết vé.',
                'url' => route('movies.index', ['trang-thai' => 'coming_soon']),
            ],
        ];
    }
}
