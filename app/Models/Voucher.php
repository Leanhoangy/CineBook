<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'description', 'type', 'value', 'max_discount', 'min_order',
    'usage_limit', 'starts_at', 'expires_at', 'is_active',
])]
class Voucher extends Model
{
    use HasFactory;

    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const TYPES = [
        self::TYPE_PERCENT => 'Giảm theo %',
        self::TYPE_FIXED => 'Giảm số tiền',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'max_discount' => 'integer',
            'min_order' => 'integer',
            'usage_limit' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (Voucher $voucher) => $voucher->code = strtoupper(trim($voucher->code)));
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Lượt dùng chỉ tính vé còn hiệu lực: khách hủy vé thì mã được hoàn lượt */
    public function confirmedBookings(): HasMany
    {
        return $this->bookings()->where('status', Booking::STATUS_CONFIRMED);
    }

    public static function findByCode(?string $code): ?self
    {
        return static::where('code', strtoupper(trim((string) $code)))->first();
    }

    /** Số tiền được giảm cho đơn có giá trị $subtotal */
    public function discountFor(int $subtotal): int
    {
        $discount = $this->type === self::TYPE_PERCENT
            ? intdiv($subtotal * $this->value, 100)
            : $this->value;

        if ($this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return min($discount, $subtotal);
    }

    /** Lý do không dùng được mã (null nếu hợp lệ) */
    public function unavailableReason(User $user, int $subtotal): ?string
    {
        return match (true) {
            ! $this->is_active => 'Mã giảm giá đã ngừng áp dụng.',
            $this->starts_at?->isFuture() => 'Mã giảm giá áp dụng từ '.$this->starts_at->format('H:i d/m/Y').'.',
            $this->expires_at?->isPast() => 'Mã giảm giá đã hết hạn.',
            $this->usage_limit !== null && $this->confirmedBookings()->count() >= $this->usage_limit => 'Mã giảm giá đã hết lượt sử dụng.',
            $this->confirmedBookings()->where('user_id', $user->id)->exists() => 'Bạn đã sử dụng mã này rồi (mỗi khách chỉ dùng 1 lần).',
            $subtotal < $this->min_order => 'Mã chỉ áp dụng cho đơn từ '.number_format($this->min_order, 0, ',', '.').' ₫.',
            default => null,
        };
    }

    /** VD: "Giảm 10% (tối đa 30.000 ₫)" hoặc "Giảm 20.000 ₫" */
    public function getLabelAttribute(): string
    {
        $money = fn (int $n) => number_format($n, 0, ',', '.').' ₫';

        if ($this->type === self::TYPE_FIXED) {
            return 'Giảm '.$money($this->value);
        }

        return "Giảm {$this->value}%".($this->max_discount ? ' (tối đa '.$money($this->max_discount).')' : '');
    }

    /** Trạng thái hiển thị trong trang quản trị */
    public function getStatusAttribute(): array
    {
        return match (true) {
            ! $this->is_active => ['secondary', 'Tạm khóa'],
            $this->expires_at?->isPast() => ['secondary', 'Hết hạn'],
            $this->starts_at?->isFuture() => ['info', 'Chưa bắt đầu'],
            $this->usage_limit !== null && ($this->used_count ?? $this->confirmedBookings()->count()) >= $this->usage_limit => ['warning', 'Hết lượt'],
            default => ['success', 'Đang áp dụng'],
        };
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        return $query->when($keyword, fn (Builder $q) => $q->where(function (Builder $q) use ($keyword) {
            $q->where('code', 'like', "%{$keyword}%")->orWhere('description', 'like', "%{$keyword}%");
        }));
    }
}
