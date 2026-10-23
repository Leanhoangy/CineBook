<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Phòng '.fake()->unique()->numberBetween(1, 99),
            'total_rows' => fake()->numberBetween(6, 10),
            'seats_per_row' => fake()->numberBetween(8, 14),
            'vip_rows' => 2,
            'is_active' => true,
        ];
    }
}
