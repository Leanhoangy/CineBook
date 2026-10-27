<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Showtime;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * Tạo lịch sử đặt vé ngẫu nhiên để trang thống kê có dữ liệu.
 */
class BookingSeeder extends Seeder
{
    private Collection $vouchers;

    /** @var array<int, int> Số lượt đã dùng của từng mã */
    private array $used = [];

    /** @var array<int, array<int, bool>> Mã khách đã dùng */
    private array $usedBy = [];

    public function run(): void
    {
        $customers = User::where('role', User::ROLE_USER)->get();
        $showtimes = Showtime::with('room')->where('start_time', '<', now()->addDays(4))->get();
        $this->vouchers = Voucher::where('is_active', true)->get();

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

                $customer = $customers->random();
                $booking = Booking::create([
                    'user_id' => $customer->id,
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

                $voucher = fake()->boolean(15) ? $this->pickVoucher($customer, $total, $createdAt, $cancelled) : null;
                $discount = $voucher?->discountFor($total) ?? 0;

                $booking->forceFill([
                    'voucher_id' => $voucher?->id,
                    'discount' => $discount,
                    'total_price' => $total - $discount,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->save();
            }
        }
    }

    /**
     * Chọn mã giảm giá hợp lệ tại thời điểm đặt (đúng thời hạn, đủ đơn tối thiểu, còn lượt, khách chưa dùng).
     * Bỏ qua tài khoản demo để khi trình bày vẫn thử được mọi mã.
     */
    private function pickVoucher(User $customer, int $total, $createdAt, bool $cancelled): ?Voucher
    {
        if ($customer->email === 'user@cinebook.test') {
            return null;
        }

        $voucher = $this->vouchers->first(fn (Voucher $v) => fake()->boolean(60)
            && (! $v->starts_at || $v->starts_at->lte($createdAt))
            && (! $v->expires_at || $v->expires_at->gt($createdAt))
            && $total >= $v->min_order
            && ($v->usage_limit === null || ($this->used[$v->id] ?? 0) < $v->usage_limit)
            && ! isset($this->usedBy[$customer->id][$v->id]));

        // Vé đã hủy không tính lượt dùng (giống quy tắc trên web)
        if ($voucher && ! $cancelled) {
            $this->used[$voucher->id] = ($this->used[$voucher->id] ?? 0) + 1;
            $this->usedBy[$customer->id][$voucher->id] = true;
        }

        return $voucher;
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
