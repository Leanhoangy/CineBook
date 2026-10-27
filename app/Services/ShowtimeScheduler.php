<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Tự động xếp lịch chiếu: mỗi phòng chiếu nối tiếp các suất từ 9h đến khoảng 23h nên không bị trùng giờ.
 * Dùng chung cho ShowtimeSeeder (tạo dữ liệu mẫu) và lệnh showtimes:refresh (bổ sung lịch hằng ngày).
 */
class ShowtimeScheduler
{
    private int $movieIndex = 0;

    /** Chuyển phim "Sắp chiếu" sang "Đang chiếu" khi đến ngày khởi chiếu */
    public function updateStatuses(): int
    {
        return Movie::where('status', 'coming_soon')
            ->whereDate('release_date', '<=', today())
            ->update(['status' => 'now_showing']);
    }

    /**
     * Xếp suất chiếu cho các ngày từ $from đến $to.
     * Phòng nào trong ngày đã có suất (ví dụ admin tự tạo) thì bỏ qua để không ghi đè.
     *
     * @return int Số suất chiếu đã tạo
     */
    public function fill(Carbon $from, Carbon $to): int
    {
        $rooms = Room::active()->get();
        $nowShowing = Movie::where('status', 'now_showing')->get();
        $ended = Movie::where('status', 'ended')->get();
        $created = 0;

        for ($date = $from->copy()->startOfDay(); $date->lte($to); $date->addDay()) {
            $movies = $this->moviesFor($date, $nowShowing, $ended);
            if ($movies->isEmpty()) {
                continue;
            }

            foreach ($rooms as $roomIndex => $room) {
                if (Showtime::where('room_id', $room->id)->whereDate('start_time', $date)->exists()) {
                    continue;
                }

                // Lệch giờ mở cửa giữa các phòng cho lịch chiếu tự nhiên hơn
                $cursor = $date->copy()->setTime(9, 0)->addMinutes($roomIndex * 20);
                if ($date->isToday() && $cursor->lt(now())) {
                    $cursor = $this->roundUp(now()->addMinutes(30));
                }

                while ($cursor->isSameDay($date) && $cursor->hour < 22) {
                    $movie = $movies[$this->movieIndex++ % $movies->count()];

                    $showtime = Showtime::create([
                        'movie_id' => $movie->id,
                        'room_id' => $room->id,
                        'start_time' => $cursor,
                        'end_time' => Showtime::calculateEndTime($cursor, $movie),
                        'price' => $this->priceFor($cursor),
                    ]);
                    $created++;

                    // Suất kế tiếp bắt đầu sau khi dọn phòng, làm tròn lên mốc 5 phút
                    $cursor = $this->roundUp($showtime->end_time);
                }
            }
        }

        return $created;
    }

    /**
     * Phim được xếp suất trong ngày: phim đang chiếu đã khởi chiếu (phim chiếu sớm được xếp từ hôm nay),
     * và với ngày trong quá khứ thì có thêm các phim nay đã ngừng chiếu.
     */
    private function moviesFor(Carbon $date, Collection $nowShowing, Collection $ended): Collection
    {
        if ($date->gte(today())) {
            return $nowShowing->values();
        }

        return $nowShowing->merge($ended)
            ->filter(fn (Movie $m) => $m->release_date->lte($date))
            ->values();
    }

    private function priceFor(Carbon $start): int
    {
        $price = $start->hour >= 17 ? 85000 : 70000;

        return $start->isWeekend() ? $price + 10000 : $price;
    }

    private function roundUp(Carbon $time): Carbon
    {
        $time = $time->copy()->second(0);
        $extra = (5 - $time->minute % 5) % 5;

        return $time->addMinutes($extra);
    }
}
