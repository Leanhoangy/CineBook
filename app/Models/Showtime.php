<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['movie_id', 'room_id', 'start_time', 'end_time', 'price'])]
class Showtime extends Model
{
    use HasFactory;

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
    public static function calculateEndTime($startTime, Movie $movie): Carbon
    {
        return Carbon::parse($startTime)
            ->addMinutes($movie->duration + (int) config('cinema.cleaning_minutes'));
    }
}
