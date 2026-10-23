<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'total_rows', 'seats_per_row', 'vip_rows', 'is_active'])]
class Room extends Model
{
    use HasFactory;

    public const MAX_ROWS = 15;

    public const MAX_SEATS_PER_ROW = 20;

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'seats_per_row' => 'integer',
            'vip_rows' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getCapacityAttribute(): int
    {
        return $this->total_rows * $this->seats_per_row;
    }

    /** Danh sách ký hiệu hàng: A, B, C... */
    public function rowLetters(): array
    {
        return array_slice(range('A', 'Z'), 0, $this->total_rows);
    }

    public function isVipRow(string $letter): bool
    {
        $index = array_search($letter, $this->rowLetters(), true);

        return $index !== false && $index >= $this->total_rows - $this->vip_rows;
    }

    /** Sơ đồ ghế dạng [hàng => [mã ghế...]] */
    public function seatMap(): array
    {
        $map = [];
        foreach ($this->rowLetters() as $letter) {
            $map[$letter] = array_map(fn ($n) => $letter.$n, range(1, $this->seats_per_row));
        }

        return $map;
    }

    public function hasSeat(string $code): bool
    {
        if (! preg_match('/^([A-Z])(\d{1,2})$/', $code, $m)) {
            return false;
        }

        return in_array($m[1], $this->rowLetters(), true)
            && (int) $m[2] >= 1 && (int) $m[2] <= $this->seats_per_row;
    }

    public function seatType(string $code): string
    {
        return $this->isVipRow(substr($code, 0, 1)) ? 'vip' : 'standard';
    }
}
