<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'phone', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    public const ROLES = [
        self::ROLE_ADMIN => 'Quản trị viên',
        self::ROLE_USER => 'Khách hàng',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Vé còn dùng được trong hôm nay (suất chưa kết thúc, chưa vào rạp), suất gần nhất đứng trước */
    public function todayTickets(): Collection
    {
        return $this->bookings()
            ->with(['showtime.movie', 'showtime.room', 'seats'])
            ->where('status', Booking::STATUS_CONFIRMED)
            ->whereNull('checked_in_at')
            ->whereHas('showtime', fn (Builder $q) => $q
                ->where('end_time', '>', now())
                ->where('start_time', '<=', today()->endOfDay()))
            ->get()
            ->sortBy('showtime.start_time')
            ->values();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? Storage::disk('public')->url($this->avatar) : null;
    }

    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name));

        return mb_strtoupper(mb_substr(end($words), 0, 1));
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        return $query->when($keyword, fn (Builder $q) => $q->where(function (Builder $q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%");
        }));
    }
}
