<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Seeder;

/**
 * Tạo đánh giá mẫu: chỉ khách đã xem phim (có vé của suất chiếu đã diễn ra) mới đánh giá,
 * giống đúng quy tắc của trang web.
 */
class ReviewSeeder extends Seeder
{
    private const COMMENTS = [
        5 => [
            'Phim quá hay, xem xong vẫn còn nổi da gà. Nhất định sẽ xem lại lần nữa!',
            'Kịch bản chặt chẽ, diễn viên diễn quá đạt. 10 điểm không có nhưng.',
            'Hình ảnh và âm thanh đỉnh cao, rất đáng tiền vé.',
            'Cả rạp vỗ tay khi hết phim, cảm xúc thật sự.',
            'Đi xem với gia đình, ai cũng khen. Rất đáng xem!',
        ],
        4 => [
            'Phim hay, nhịp phim ổn, chỉ có đoạn giữa hơi chậm một chút.',
            'Diễn xuất tốt, kỹ xảo đẹp. Đáng để ra rạp.',
            'Nội dung cuốn hút, cái kết khá bất ngờ.',
            'Xem giải trí rất ổn, nhạc phim hay.',
            'Không xuất sắc nhưng rất đáng xem cuối tuần.',
        ],
        3 => [
            'Phim tạm ổn, xem một lần cho biết.',
            'Mở đầu hấp dẫn nhưng về cuối hơi đuối.',
            'Kỹ xảo được nhưng kịch bản còn nhiều chỗ chưa hợp lý.',
            'Không như kỳ vọng lắm nhưng cũng không tệ.',
        ],
        2 => [
            'Hơi thất vọng, phim kéo dài không cần thiết.',
            'Nội dung khá dễ đoán, xem nửa đầu là biết kết.',
            'Trailer hay hơn phim.',
        ],
        1 => [
            'Không hợp gu mình, ngủ gật giữa phim.',
            'Kịch bản rời rạc, khó hiểu.',
        ],
    ];

    public function run(): void
    {
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
                'content' => fake()->boolean(80) ? fake()->randomElement(self::COMMENTS[$rating]) : null,
            ]);
            $review->forceFill(['created_at' => $reviewedAt, 'updated_at' => $reviewedAt])->save();
        }

        // Vài bình luận quảng cáo bị admin ẩn để minh họa chức năng kiểm duyệt
        Review::inRandomOrder()->take(3)->get()->each->update([
            'content' => 'Bán vé xem phim giá rẻ, inbox zalo 0909xxxxxx!!!',
            'is_hidden' => true,
        ]);
    }
}
