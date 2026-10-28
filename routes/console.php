<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mỗi ngày lúc 00:05: cập nhật trạng thái phim (khởi chiếu / ngừng chiếu) và bổ sung lịch chiếu cho 7 ngày tới
// (khi phát triển trên máy cá nhân, chạy "php artisan schedule:work" để bộ lập lịch hoạt động)
Schedule::command('showtimes:refresh')->dailyAt('00:05');

// Mỗi phút: tự hủy vé chưa thanh toán đã quá hạn (trước giờ chiếu 30 phút) để mở bán lại ghế
Schedule::command('bookings:release-unpaid')->everyMinute();

// Sáng thứ Hai lấy thêm phim mới từ Moveek. Chỉ chạy trên server thật để không gửi request liên tục khi phát triển
Schedule::command('movies:crawl --ended=0 --import')->weeklyOn(1, '03:00')->environments('production');
