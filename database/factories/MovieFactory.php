<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movie>
 */
class MovieFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->words(3, true)),
            'description' => fake()->paragraph(4),
            'director' => fake()->name(),
            'cast' => implode(', ', [fake()->name(), fake()->name(), fake()->name()]),
            'duration' => fake()->numberBetween(85, 170),
            'release_date' => fake()->dateTimeBetween('-2 months', '+1 month'),
            'age_rating' => fake()->randomElement(array_keys(Movie::AGE_RATINGS)),
            'status' => 'now_showing',
        ];
    }

    public function comingSoon(): static
    {
        return $this->state(fn () => [
            'status' => 'coming_soon',
            'release_date' => fake()->dateTimeBetween('+1 week', '+2 months'),
        ]);
    }
}
