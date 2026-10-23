<?php

namespace Database\Factories;

use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Showtime>
 */
class ShowtimeFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 6))->setTime(fake()->numberBetween(9, 21), 0);

        return [
            'movie_id' => Movie::factory(),
            'room_id' => Room::factory(),
            'start_time' => $start,
            'end_time' => (clone $start)->addHours(2),
            'price' => fake()->randomElement([75000, 85000, 95000]),
        ];
    }

    /** Đồng bộ giờ kết thúc theo thời lượng thật của phim */
    public function configure(): static
    {
        return $this->afterMaking(function (Showtime $showtime) {
            if ($showtime->movie) {
                $showtime->end_time = Showtime::calculateEndTime($showtime->start_time, $showtime->movie);
            }
        });
    }
}
