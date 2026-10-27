<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'movie_id', 'rating', 'content', 'is_hidden'])]
class Review extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_hidden' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class)->withTrashed();
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        return $query->when($keyword, fn (Builder $q) => $q->where(function (Builder $q) use ($keyword) {
            $q->where('content', 'like', "%{$keyword}%")
                ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$keyword}%"))
                ->orWhereHas('movie', fn (Builder $m) => $m->where('title', 'like', "%{$keyword}%"));
        }));
    }
}
