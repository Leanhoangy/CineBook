<?php

namespace Database\Factories;

use App\Models\Combo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Combo>
 */
class ComboFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Combo '.fake()->unique()->word(),
            'description' => '1 bắp (L) + 1 nước (L)',
            'price' => fake()->randomElement([59000, 85000, 115000]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
