<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Xếp lịch chiếu cho các phim đang chiếu trong 14 ngày trước và 7 ngày tới.
 * Mỗi phòng chiếu nối tiếp nhau từ 9h đến khoảng 23h nên không bị trùng giờ.
 */
class ShowtimeSeeder extends Seeder
{
    public function run(): void
    {
        $allMovies = Movie::where('status', 'now_showing')->get();
        $rooms = Room::all();
        $movieIndex = 0;

        for ($day = -14; $day <= 7; $day++) {
            $date = today()->addDays($day);

            // Chỉ xếp suất cho phim đã khởi chiếu vào ngày đó; phim chiếu sớm (suất đặc biệt) được xếp từ hôm nay
            $movies = $allMovies->filter(fn (Movie $m) => $m->release_date->lte(max($date, today())))->values();
            if ($movies->isEmpty()) {
                continue;
            }

            foreach ($rooms as $roomIndex => $room) {
                // Lệch giờ mở cửa giữa các phòng cho lịch chiếu tự nhiên hơn
                $cursor = $date->copy()->setTime(9, 0)->addMinutes($roomIndex * 20);

                while ($cursor->hour < 22) {
                    $movie = $movies[$movieIndex++ % $movies->count()];

                    $showtime = Showtime::create([
                        'movie_id' => $movie->id,
                        'room_id' => $room->id,
                        'start_time' => $cursor,
                        'end_time' => Showtime::calculateEndTime($cursor, $movie),
                        'price' => $this->priceFor($cursor),
                    ]);

                    // Suất kế tiếp bắt đầu sau khi dọn phòng, làm tròn lên mốc 5 phút
                    $cursor = $this->roundUp($showtime->end_time);
                }
            }
        }
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
