<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Tạo lịch sử đặt vé ngẫu nhiên để trang thống kê có dữ liệu.
 */
class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', User::ROLE_USER)->get();
        $showtimes = Showtime::with('room')->where('start_time', '<', now()->addDays(4))->get();

        foreach ($showtimes as $showtime) {
            $taken = [];
            $bookingCount = fake()->numberBetween(0, $showtime->start_time->isPast() ? 7 : 4);

            for ($i = 0; $i < $bookingCount; $i++) {
                $seats = $this->pickSeats($showtime, $taken, fake()->numberBetween(1, 4));
                if (! $seats) {
                    break;
                }

                $cancelled = fake()->boolean(10);
                if (! $cancelled) {
                    $taken = array_merge($taken, $seats);
                }

                // Vé được đặt trước giờ chiếu từ 2 đến 72 giờ, và không thể nằm ở tương lai
                $createdAt = $showtime->start_time->isPast()
                    ? $showtime->start_time->copy()->subHours(fake()->numberBetween(2, 72))
                    : now()->subMinutes(fake()->numberBetween(5, 3 * 24 * 60));

                $booking = Booking::create([
                    'user_id' => $customers->random()->id,
                    'showtime_id' => $showtime->id,
                    'total_price' => 0,
                    'status' => $cancelled ? Booking::STATUS_CANCELLED : Booking::STATUS_CONFIRMED,
                ]);

                $total = 0;
                foreach ($seats as $code) {
                    $type = $showtime->room->seatType($code);
                    $price = $showtime->seatPrice($type);
                    $total += $price;
                    $booking->seats()->create(['seat_code' => $code, 'seat_type' => $type, 'price' => $price]);
                }

                $booking->forceFill([
                    'total_price' => $total,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->save();
            }
        }
    }

    /** Chọn một dãy ghế liền nhau còn trống trong cùng một hàng */
    private function pickSeats(Showtime $showtime, array $taken, int $count): array
    {
        $map = $showtime->room->seatMap();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $row = fake()->randomElement(array_keys($map));
            $start = fake()->numberBetween(0, max(0, count($map[$row]) - $count));
            $seats = array_slice($map[$row], $start, $count);

            if (! array_intersect($seats, $taken)) {
                return $seats;
            }
        }

        return [];
    }
}
