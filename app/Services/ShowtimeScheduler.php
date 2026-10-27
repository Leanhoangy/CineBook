<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Tự động xếp lịch chiếu: mỗi phòng chiếu nối tiếp các suất từ 9h đến khoảng 23h nên không bị trùng giờ.
 * Phim nào cũng có suất mỗi ngày, phim mới khởi chiếu được nhiều suất hơn (giống rạp thật).
 * Dùng chung cho ShowtimeSeeder (tạo dữ liệu mẫu) và lệnh showtimes:refresh (bổ sung lịch hằng ngày).
 */
class ShowtimeScheduler
{
    private const OPEN_HOUR = 9;

    /** Suất cuối cùng trong ngày phải bắt đầu trước giờ này */
    private const LAST_START_HOUR = 23;

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

            // Giờ trống kế tiếp của từng phòng; giờ mở cửa lệch nhau 10 phút cho lịch chiếu tự nhiên hơn
            $cursors = [];
            foreach ($rooms as $roomIndex => $room) {
                if (Showtime::where('room_id', $room->id)->whereDate('start_time', $date)->exists()) {
                    continue;
                }

                $cursor = $date->copy()->setTime(self::OPEN_HOUR, 0)->addMinutes($roomIndex * 10);
                $cursors[$room->id] = $date->isToday() && $cursor->lt(now()) ? $this->roundUp(now()->addMinutes(30)) : $cursor;
            }
            if (! $cursors) {
                continue;
            }

            $closing = $date->copy()->setTime(self::LAST_START_HOUR, 0);
            $queue = $this->dailyQueue($movies, $date, $this->capacity($cursors, $closing, $movies));

            // Xếp lần lượt từng suất vào phòng rảnh sớm nhất; còn giờ trống thì quay vòng lại danh sách
            for ($i = 0; ; $i++) {
                $movie = $queue[$i % count($queue)];
                $roomId = array_search(min($cursors), $cursors);
                if ($cursors[$roomId]->gte($closing)) {
                    break;
                }

                $showtime = Showtime::create([
                    'movie_id' => $movie->id,
                    'room_id' => $roomId,
                    'start_time' => $cursors[$roomId],
                    'end_time' => Showtime::calculateEndTime($cursors[$roomId], $movie),
                    'price' => $this->priceFor($cursors[$roomId]),
                ]);
                $created++;

                // Suất kế tiếp bắt đầu sau khi dọn phòng, làm tròn lên mốc 5 phút
                $cursors[$roomId] = $this->roundUp($showtime->end_time);
            }
        }

        return $created;
    }

    /** Ước lượng (làm tròn xuống) số suất các phòng còn chiếu được trong ngày, dựa trên thời lượng phim trung bình */
    private function capacity(array $cursors, Carbon $closing, Collection $movies): int
    {
        $slot = $movies->avg('duration') + (int) config('cinema.cleaning_minutes');

        return collect($cursors)->sum(fn (Carbon $c) => $c->lt($closing) ? (int) floor($c->diffInMinutes($closing) / $slot) : 0);
    }

    /**
     * Thứ tự các suất trong ngày. Số suất của mỗi phim tỷ lệ với độ "hot":
     * khởi chiếu trong 2 tuần gấp 4, trong 6 tuần gấp 2 phim cũ, và phim nào cũng có ít nhất 1 suất.
     * Các suất của cùng một phim được rải đều từ sáng đến tối, bắt đầu từ một mốc ngẫu nhiên.
     */
    private function dailyQueue(Collection $movies, Carbon $date, int $capacity): array
    {
        $weights = $movies->map(fn (Movie $m) => match (true) {
            $m->release_date->gt($date->copy()->subDays(14)) => 4,
            $m->release_date->gt($date->copy()->subDays(42)) => 2,
            default => 1,
        });

        $shows = [];
        foreach ($movies as $i => $movie) {
            $count = max(1, (int) round($capacity * $weights[$i] / $weights->sum()));
            $offset = mt_rand() / mt_getrandmax();

            for ($n = 0; $n < $count; $n++) {
                $shows[] = ['at' => ($n + $offset) / $count, 'movie' => $movie];
            }
        }

        usort($shows, fn ($a, $b) => $a['at'] <=> $b['at']);

        return array_column($shows, 'movie');
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
