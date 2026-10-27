<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'title', 'description', 'director', 'cast', 'duration', 'release_date', 'end_date',
    'age_rating', 'poster', 'trailer_url', 'status',
])]
class Movie extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = [
        'now_showing' => 'Đang chiếu',
        'coming_soon' => 'Sắp chiếu',
        'ended' => 'Ngừng chiếu',
    ];

    public const AGE_RATINGS = [
        'P' => 'Mọi lứa tuổi',
        'T13' => 'Từ 13 tuổi',
        'T16' => 'Từ 16 tuổi',
        'T18' => 'Từ 18 tuổi',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'end_date' => 'date',
            'duration' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Movie $movie) {
            if ($movie->isDirty('title') || ! $movie->slug) {
                $movie->slug = static::uniqueSlug($movie->title, $movie->id);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'phim';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Khách đã xem phim (có vé hợp lệ của suất đã bắt đầu) mới được đánh giá */
    public function canBeReviewedBy(?User $user): bool
    {
        return $user !== null && ! $user->isAdmin() && $user->bookings()
            ->where('status', Booking::STATUS_CONFIRMED)
            ->whereHas('showtime', fn (Builder $q) => $q
                ->where('movie_id', $this->id)
                ->where('start_time', '<=', now()))
            ->exists();
    }

    public function upcomingShowtimes(): HasMany
    {
        return $this->showtimes()->where('start_time', '>', now())->orderBy('start_time');
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        return $query->when($keyword, fn (Builder $q) => $q->where(function (Builder $q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
                ->orWhere('director', 'like', "%{$keyword}%")
                ->orWhere('cast', 'like', "%{$keyword}%");
        }));
    }

    public function scopeOfGenre(Builder $query, ?string $genreSlug): Builder
    {
        return $query->when($genreSlug, fn (Builder $q) => $q->whereHas('genres', fn (Builder $g) => $g->where('slug', $genreSlug)));
    }

    public function scopeOfStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status && isset(self::STATUSES[$status]), fn (Builder $q) => $q->where('status', $status));
    }

    public function getPosterUrlAttribute(): ?string
    {
        if (! $this->poster) {
            return null;
        }

        return Str::startsWith($this->poster, ['http://', 'https://'])
            ? $this->poster
            : Storage::disk('public')->url($this->poster);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getDurationLabelAttribute(): string
    {
        $h = intdiv($this->duration, 60);
        $m = $this->duration % 60;

        return $h ? "{$h} giờ {$m} phút" : "{$m} phút";
    }

    /** Màu nền cho poster dự phòng khi phim chưa có ảnh */
    public function getPosterHueAttribute(): int
    {
        return crc32($this->title) % 360;
    }

    public function getTrailerEmbedUrlAttribute(): ?string
    {
        if (! $this->trailer_url) {
            return null;
        }

        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $this->trailer_url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        return null;
    }
}
