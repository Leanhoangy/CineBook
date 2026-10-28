<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['code', 'user_id', 'showtime_id', 'voucher_id', 'total_price', 'discount', 'status', 'note', 'paid_at', 'payment_method', 'payment_ref', 'checked_in_at'])]
class Booking extends Model
{
    use HasFactory;

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_CONFIRMED => 'Đã xác nhận',
        self::STATUS_CANCELLED => 'Đã hủy',
    ];

    public const PAYMENT_VNPAY = 'vnpay';

    public const PAYMENT_COUNTER = 'counter';

    public const PAYMENT_METHODS = [
        self::PAYMENT_VNPAY => 'VNPay',
        self::PAYMENT_COUNTER => 'Tại quầy',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'integer',
            'discount' => 'integer',
            'paid_at' => 'datetime',
            'checked_in_at' => 'datetime',
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

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /** Tiền vé trước khi áp mã giảm giá */
    public function getSubtotalAttribute(): int
    {
        return $this->total_price + $this->discount;
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

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function getPaymentLabelAttribute(): string
    {
        return $this->isPaid()
            ? 'Đã thanh toán ('.(self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method).')'
            : 'Chưa thanh toán';
    }

    /** Thanh toán online được khi vé còn hiệu lực, chưa trả tiền và suất chiếu chưa bắt đầu */
    public function canBePaidOnline(): bool
    {
        return $this->isConfirmed() && ! $this->isPaid() && $this->showtime->start_time->isFuture();
    }

    public function markPaid(string $method, ?string $ref = null): void
    {
        $this->update(['paid_at' => now(), 'payment_method' => $method, 'payment_ref' => $ref]);
    }

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    /**
     * Lý do không cho vé vào rạp lúc này (null = soát được).
     * Vé chưa thanh toán vẫn soát được nhưng nhân viên phải thu tiền trước.
     */
    public function checkInProblem(): ?string
    {
        $start = $this->showtime->start_time;
        $opensAt = $start->copy()->subMinutes((int) config('cinema.checkin_before_minutes'));

        return match (true) {
            ! $this->isConfirmed() => 'Vé đã bị hủy.',
            $this->isCheckedIn() => 'Vé đã được soát lúc '.$this->checked_in_at->format('H:i d/m/Y').'. Không cho vào lần thứ hai.',
            $this->showtime->end_time->isPast() => 'Suất chiếu đã kết thúc lúc '.$this->showtime->end_time->format('H:i d/m/Y').'.',
            $opensAt->isFuture() => 'Chưa tới giờ soát vé. Suất chiếu lúc '.$start->format('H:i').' ngày '.$start->format('d/m/Y')
                .', mở soát vé từ '.$opensAt->format('H:i').'.',
            default => null,
        };
    }

    /**
     * Khách chỉ được tự hủy khi vé còn hiệu lực, chưa thanh toán online và còn đủ thời gian trước giờ chiếu.
     * Vé đã thanh toán không tự hủy được (giống các rạp thật: vé đã mua không hoàn tiền).
     */
    public function canBeCancelledByCustomer(): bool
    {
        return $this->isConfirmed()
            && ! $this->isPaid()
            && $this->showtime->start_time->gt(now()->addMinutes((int) config('cinema.cancel_before_minutes')));
    }
}
