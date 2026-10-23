<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['code', 'user_id', 'showtime_id', 'total_price', 'status', 'note'])]
class Booking extends Model
{
    use HasFactory;

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_CONFIRMED => 'Đã xác nhận',
        self::STATUS_CANCELLED => 'Đã hủy',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->code ??= static::generateCode();
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'CB'.strtoupper(Str::random(6));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        return $query->when($keyword, fn (Builder $q) => $q->where(function (Builder $q) use ($keyword) {
            $q->where('code', 'like', "%{$keyword}%")
                ->orWhereHas('user', fn (Builder $u) => $u
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%"))
                ->orWhereHas('showtime.movie', fn (Builder $m) => $m->where('title', 'like', "%{$keyword}%"));
        }));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getSeatListAttribute(): string
    {
        return $this->seats->pluck('seat_code')->implode(', ');
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /** Khách chỉ được tự hủy khi vé còn hiệu lực và còn đủ thời gian trước giờ chiếu */
    public function canBeCancelledByCustomer(): bool
    {
        return $this->isConfirmed()
            && $this->showtime->start_time->gt(now()->addMinutes((int) config('cinema.cancel_before_minutes')));
    }
}
