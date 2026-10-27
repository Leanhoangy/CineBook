<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mỗi ngày lúc 00:05 bổ sung lịch chiếu cho 7 ngày tới (chạy "php artisan schedule:work" khi phát triển)
Schedule::command('showtimes:refresh')->dailyAt('00:05');
