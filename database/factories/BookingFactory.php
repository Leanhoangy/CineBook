<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'showtime_id' => Showtime::factory(),
            'total_price' => 0,
            'status' => Booking::STATUS_CONFIRMED,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => Booking::STATUS_CANCELLED]);
    }

    /** Tạo kèm danh sách ghế và tự tính tổng tiền */
    public function withSeats(array $seatCodes): static
    {
        return $this->afterCreating(function (Booking $booking) use ($seatCodes) {
            $showtime = $booking->showtime()->with('room')->first();
            $total = 0;

            foreach ($seatCodes as $code) {
                $type = $showtime->room->seatType($code);
                $price = $showtime->seatPrice($type);
                $total += $price;
                $booking->seats()->create(['seat_code' => $code, 'seat_type' => $type, 'price' => $price]);
            }

            $booking->update(['total_price' => $total]);
        });
    }
}
