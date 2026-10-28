<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable(['movie_id', 'room_id', 'start_time', 'end_time', 'price'])]
/**
 * Suất chiếu. Suất bị rạp hủy được xóa mềm (deleted_at): tự ẩn khỏi mọi trang và truy vấn,
 * nhưng vé của khách vẫn xem được thông tin suất cũ.
 */
class Showtime extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'price' => 'integer',
        ];
    }

    public function movie(): BelongsTo
    {
        // withTrashed: suất chiếu cũ vẫn hiển thị được tên phim dù phim đã bị xóa mềm
        return $this->belongsTo(Movie::class)->withTrashed();
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function confirmedBookings(): HasMany
    {
        return $this->bookings()->where('status', Booking::STATUS_CONFIRMED);
    }

    /** Suất đã bị rạp hủy */
    public function isCancelled(): bool
    {
        return $this->trashed();
    }

    /** Lúc mở soát vé: trước giờ chiếu checkin_before_minutes phút */
    public function checkInOpensAt(): Carbon
    {
        return $this->start_time->copy()->subMinutes((int) config('cinema.checkin_before_minutes'));
    }

    /** Suất đang trong giờ soát vé: từ lúc mở soát đến khi phim kết thúc */
    public function scopeCheckInOpen(Builder $query): Builder
    {
        return $query->where('start_time', '<=', now()->addMinutes((int) config('cinema.checkin_before_minutes')))
            ->where('end_time', '>', now());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_time', '>', now());
    }

    /** Mã các ghế đã được đặt (chỉ tính vé chưa hủy) */
    public function bookedSeatCodes(): array
    {
        return BookingSeat::query()
            ->whereHas('booking', fn (Builder $q) => $q
                ->where('showtime_id', $this->id)
                ->where('status', Booking::STATUS_CONFIRMED))
            ->pluck('seat_code')
            ->all();
    }

    public function seatPrice(string $seatType): int
    {
        return $seatType === 'vip'
            ? $this->price + (int) config('cinema.vip_surcharge')
            : $this->price;
    }

    public function isBookable(): bool
    {
        return $this->start_time->isFuture()
            && $this->room?->is_active
            && $this->movie && ! $this->movie->trashed()
            && $this->movie->status !== 'ended'; // phim sắp chiếu: cho đặt vé trước
    }

    /** Tính giờ kết thúc = giờ bắt đầu + thời lượng phim + thời gian dọn phòng */
    /** Giá vé ghế thường theo bảng giá (config/cinema.php): khung giờ, cuối tuần, "Thứ Ba vui vẻ" */
    public static function standardPriceFor(Carbon $start): int
    {
        $pricing = config('cinema.pricing');

        if ($start->isTuesday()) {
            return $pricing['tuesday'];
        }

        $price = match (true) {
            $start->hour < 12 => $pricing['morning'],
            $start->hour < 17 => $pricing['afternoon'],
            default => $pricing['evening'],
        };

        return $start->isWeekend() ? $price + $pricing['weekend_surcharge'] : $price;
    }

    public static function calculateEndTime($startTime, Movie $movie): Carbon
    {
        return Carbon::parse($startTime)
            ->addMinutes($movie->duration + (int) config('cinema.cleaning_minutes'));
    }
}
