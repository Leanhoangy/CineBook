<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Tạo lịch sử đặt vé giống rạp thật để trang thống kê và sơ đồ ghế có dữ liệu:
 * - Độ kín ghế tùy độ hot của phim, khung giờ và cuối tuần (phim mới suất tối cuối tuần gần kín, suất sáng phim cũ thưa).
 * - Suất chưa chiếu vẫn đang mở bán nên càng xa càng ít vé.
 * - Khách đi theo nhóm 1–4 người ngồi liền nhau, thích giữa/cuối rạp và giữa hàng; 2 hàng sát màn hình chỉ có người ngồi khi gần hết chỗ.
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
        $showtimes = Showtime::with(['room', 'movie'])->where('start_time', '<', now()->addDays(4))->get();
        $this->vouchers = Voucher::where('is_active', true)->get();

        // Ghi hàng chục nghìn vé trong một transaction cho nhanh
        DB::transaction(fn () => $showtimes->each(fn (Showtime $showtime) => $this->fillShowtime($showtime, $customers)));
    }

    private function fillShowtime(Showtime $showtime, Collection $customers): void
    {
        $taken = [];
        $target = (int) round($showtime->room->capacity * $this->occupancy($showtime));

        while (count($taken) < $target) {
            $size = fake()->randomElement([1, 1, 1, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 3, 3, 3, 4, 4, 4, 4]);
            $seats = $this->pickSeats($showtime->room, $taken, min($size, $target - count($taken)));
            if (! $seats) {
                break;
            }

            $cancelled = fake()->boolean(8);
            if (! $cancelled) {
                $taken += array_fill_keys($seats, true);
            }

            $this->createBooking($showtime, $customers->random(), $seats, $cancelled);
        }
    }

    /** Tỷ lệ ghế đã bán mong muốn của một suất chiếu (0 – 0,85) */
    private function occupancy(Showtime $showtime): float
    {
        $start = $showtime->start_time;
        $daysSinceRelease = $showtime->movie->release_date->diffInDays($start, false);

        $popularity = match (true) {
            $daysSinceRelease < 0 => 0.3,   // suất chiếu sớm
            $daysSinceRelease <= 14 => 0.5, // phim mới ra
            $daysSinceRelease <= 42 => 0.28,
            default => 0.12,
        };
        // Mỗi phim một sức hút riêng (cố định theo tên phim): có phim "cháy vé", có phim ế
        $appeal = 0.6 + (crc32($showtime->movie->title) % 80) / 100;

        $timeOfDay = match (true) {
            $start->hour < 12 => 0.45,
            $start->hour < 17 => 0.7,
            $start->hour < 21 => 1.2,
            default => 0.85,
        };

        $rate = $popularity * $appeal * $timeOfDay * ($start->isWeekend() ? 1.25 : 1) * fake()->randomFloat(2, 0.75, 1.25);

        // Suất chưa chiếu vẫn đang bán vé: càng xa ngày chiếu càng ít người đặt
        if ($start->isFuture()) {
            $rate *= max(0.15, 1 - now()->diffInHours($start) / 24 * 0.3);
        }

        return min($rate, 0.85);
    }

    private function createBooking(Showtime $showtime, User $customer, array $seats, bool $cancelled): void
    {
        // Vé được đặt trước giờ chiếu từ 2 đến 72 giờ, và không thể nằm ở tương lai
        $createdAt = $showtime->start_time->isPast()
            ? $showtime->start_time->copy()->subHours(fake()->numberBetween(2, 72))
            : now()->subMinutes(fake()->numberBetween(5, 3 * 24 * 60));

        $rows = [];
        $total = 0;
        foreach ($seats as $code) {
            $type = $showtime->room->seatType($code);
            $price = $showtime->seatPrice($type);
            $total += $price;
            $rows[] = ['seat_code' => $code, 'seat_type' => $type, 'price' => $price];
        }

        $voucher = fake()->boolean(15) ? $this->pickVoucher($customer, $total, $createdAt, $cancelled) : null;
        $discount = $voucher?->discountFor($total) ?? 0;
        [$paidAt, $method] = $cancelled ? [null, null] : $this->payment($showtime, $createdAt);

        $booking = new Booking([
            'user_id' => $customer->id,
            'showtime_id' => $showtime->id,
            'voucher_id' => $voucher?->id,
            'discount' => $discount,
            'total_price' => $total - $discount,
            'status' => $cancelled ? Booking::STATUS_CANCELLED : Booking::STATUS_CONFIRMED,
            'paid_at' => $paidAt,
            'payment_method' => $method,
            'payment_ref' => $method === Booking::PAYMENT_VNPAY ? fake()->numerify('14######') : null,
        ]);
        $booking->created_at = $booking->updated_at = $createdAt;
        $booking->save();

        BookingSeat::insert(array_map(fn (array $row) => $row + ['booking_id' => $booking->id], $rows));
    }

    /**
     * Khoảng 55% khách thanh toán online qua VNPay ngay sau khi đặt; số còn lại trả tại quầy trước giờ chiếu,
     * nên vé của suất sắp chiếu có thể vẫn chưa thanh toán. Vé đã hủy luôn là vé chưa trả tiền.
     *
     * @return array{0: ?Carbon, 1: ?string}
     */
    private function payment(Showtime $showtime, Carbon $createdAt): array
    {
        if (fake()->boolean(55)) {
            return [$createdAt->copy()->addMinutes(fake()->numberBetween(1, 8))->min(now()), Booking::PAYMENT_VNPAY];
        }

        return $showtime->start_time->isPast()
            ? [$showtime->start_time->copy()->subMinutes(fake()->numberBetween(10, 45)), Booking::PAYMENT_COUNTER]
            : [null, null];
    }

    /**
     * Chọn mã giảm giá hợp lệ tại thời điểm đặt (đúng thời hạn, đủ đơn tối thiểu, còn lượt, khách chưa dùng).
     * Mã giới hạn lượt dùng hết như thật (VD: THANHVIENMOI cho 20 khách đầu tiên).
     * Bỏ qua tài khoản demo để khi trình bày vẫn thử được các mã còn lượt.
     */
    private function pickVoucher(User $customer, int $total, Carbon $createdAt, bool $cancelled): ?Voucher
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

    /**
     * Chọn $count ghế liền nhau còn trống trong cùng một hàng, ngẫu nhiên có trọng số:
     * hàng giữa/cuối và ghế giữa hàng được chọn nhiều nhất.
     * 2 hàng sát màn hình không ai chọn, trừ khi các hàng phía sau đã hết chỗ cho cả nhóm.
     *
     * @param  array<string, true>  $taken
     */
    private function pickSeats(Room $room, array $taken, int $count): array
    {
        $rows = $room->rowLetters();

        return $this->weightedPick($room, array_slice($rows, 2, preserve_keys: true), $taken, $count)
            ?: $this->weightedPick($room, array_slice($rows, 0, 2, true), $taken, $count);
    }

    /** @param  array<int, string>  $rows  [chỉ số hàng => ký hiệu hàng] */
    private function weightedPick(Room $room, array $rows, array $taken, int $count): array
    {
        $lastRow = max($room->total_rows - 1, 1);
        $perRow = $room->seats_per_row;
        $options = [];

        foreach ($rows as $i => $row) {
            $rowWeight = 1.2 - abs($i / $lastRow - 0.7); // đẹp nhất khoảng 70% chiều sâu rạp

            for ($start = 1; $start + $count - 1 <= $perRow; $start++) {
                $seats = array_map(fn (int $k) => $row.($start + $k), range(0, $count - 1));
                foreach ($seats as $seat) {
                    if (isset($taken[$seat])) {
                        continue 2;
                    }
                }

                // Lệch khỏi giữa hàng (0 = chính giữa, 0,5 = sát lối đi)
                $offCenter = abs(($start + ($count - 1) / 2 - ($perRow + 1) / 2) / $perRow);
                $options[] = [$seats, $rowWeight * (1 - $offCenter * 1.4)];
            }
        }

        if (! $options) {
            return [];
        }

        $pick = fake()->randomFloat(4, 0, array_sum(array_column($options, 1)));
        foreach ($options as [$seats, $weight]) {
            if (($pick -= $weight) <= 0) {
                return $seats;
            }
        }

        return end($options)[0];
    }
}
