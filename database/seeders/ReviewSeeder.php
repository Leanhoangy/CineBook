<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Review;
use Illuminate\Database\Seeder;

/**
 * Tạo đánh giá mẫu: chỉ khách đã xem phim (có vé của suất chiếu đã diễn ra) mới đánh giá,
 * giống đúng quy tắc của trang web. Nội dung lấy từ data/review_comments.php theo thể loại phim.
 */
class ReviewSeeder extends Seeder
{
    /** @var array{groups: array<string, list<string>>, comments: array, general: array} */
    private array $pool;

    /** @var array<int, list<string>> Câu đã dùng cho từng phim, để một phim không có 2 bình luận giống hệt nhau */
    private array $used = [];

    public function run(): void
    {
        $this->pool = require __DIR__.'/data/review_comments.php';
        $genres = Movie::with('genres')->get()->mapWithKeys(fn (Movie $m) => [$m->id => $m->genres->pluck('name')->all()]);

        // Mỗi cặp (khách, phim) đã xem lấy suất chiếu gần nhất để làm mốc thời gian đánh giá
        $watched = Booking::query()
            ->join('showtimes', 'showtimes.id', '=', 'bookings.showtime_id')
            ->where('bookings.status', Booking::STATUS_CONFIRMED)
            ->where('showtimes.start_time', '<=', now()->subHours(3))
            ->groupBy('bookings.user_id', 'showtimes.movie_id')
            ->selectRaw('bookings.user_id, showtimes.movie_id, MAX(showtimes.start_time) as watched_at')
            ->get();

        foreach ($watched as $row) {
            if (! fake()->boolean(55)) {
                continue;
            }

            $rating = fake()->randomElement([5, 5, 5, 5, 5, 5, 5, 4, 4, 4, 4, 4, 4, 4, 3, 3, 3, 3, 2, 2, 1]);
            $reviewedAt = fake()->dateTimeBetween($row->watched_at.' +3 hours', 'now');

            $review = Review::create([
                'user_id' => $row->user_id,
                'movie_id' => $row->movie_id,
                'rating' => $rating,
                'content' => fake()->boolean(80) ? $this->comment($row->movie_id, $genres[$row->movie_id] ?? [], $rating) : null,
            ]);
            $review->forceFill(['created_at' => $reviewedAt, 'updated_at' => $reviewedAt])->save();
        }

        // Vài bình luận quảng cáo bị admin ẩn để minh họa chức năng kiểm duyệt
        Review::inRandomOrder()->take(3)->get()->each->update([
            'content' => 'Bán vé xem phim giá rẻ, inbox zalo 0909xxxxxx!!!',
            'is_hidden' => true,
        ]);
    }

    /** Chọn bình luận hợp với thể loại phim và số sao, không trùng với bình luận khác của cùng phim */
    private function comment(int $movieId, array $genres, int $rating): ?string
    {
        $tier = min(max($rating, 2), 5);
        $group = collect($this->pool['groups'])->search(fn (array $names) => array_intersect($names, $genres));

        $candidates = array_diff(
            array_merge($this->pool['comments'][$group][$tier] ?? [], $this->pool['general'][$tier]),
            $this->used[$movieId] ?? [],
        );

        if (! $candidates) {
            return null;
        }

        return $this->used[$movieId][] = fake()->randomElement($candidates);
    }
}
