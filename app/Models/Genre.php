<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description'])]
class Genre extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Genre $genre) {
            $genre->slug = Str::slug($genre->name);
        });
    }

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class);
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        return $query->when($keyword, fn (Builder $q) => $q->where('name', 'like', "%{$keyword}%"));
    }
}
