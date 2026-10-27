<?php

namespace Database\Factories;

use App\Models\Movie;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'movie_id' => Movie::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'content' => fake()->sentence(12),
            'is_hidden' => false,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_hidden' => true]);
    }
}
