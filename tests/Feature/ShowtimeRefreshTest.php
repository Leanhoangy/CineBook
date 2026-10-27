<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowtimeRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_releases_coming_soon_movies_and_schedules_upcoming_days(): void
    {
        Room::factory()->create();
        $released = Movie::factory()->create(['status' => 'coming_soon', 'release_date' => today()]);
        $later = Movie::factory()->create(['status' => 'coming_soon', 'release_date' => today()->addWeek()]);

        $this->artisan('showtimes:refresh', ['--days' => 3])->assertSuccessful();

        $this->assertSame('now_showing', $released->fresh()->status);
        $this->assertSame('coming_soon', $later->fresh()->status);
        $this->assertTrue(Showtime::whereDate('start_time', today()->addDays(3))->exists());
        $this->assertFalse(Showtime::where('start_time', '<', now())->exists());
        $this->assertFalse(Showtime::where('movie_id', $later->id)->exists());
    }

    public function test_refresh_does_not_touch_rooms_that_already_have_showtimes(): void
    {
        $room = Room::factory()->create();
        $movie = Movie::factory()->create(['duration' => 100]);
        Showtime::factory()->create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => today()->addDay()->setTime(20, 0),
        ]);

        $this->artisan('showtimes:refresh', ['--days' => 1])->assertSuccessful();

        $this->assertSame(1, Showtime::whereDate('start_time', today()->addDay())->count());
    }

    public function test_every_movie_is_scheduled_daily_and_new_releases_get_more_showtimes(): void
    {
        Room::factory()->count(4)->create();
        $new = Movie::factory()->create(['duration' => 100, 'release_date' => today()->subDays(3)]);
        $old = Movie::factory()->count(5)->create(['duration' => 100, 'release_date' => today()->subMonths(3)]);

        $this->artisan('showtimes:refresh', ['--days' => 3])->assertSuccessful();

        for ($day = 1; $day <= 3; $day++) {
            $counts = Showtime::whereDate('start_time', today()->addDays($day))
                ->selectRaw('movie_id, COUNT(*) as total')->groupBy('movie_id')->pluck('total', 'movie_id');

            $this->assertCount(6, $counts, 'Phim nào cũng phải có suất mỗi ngày');
            $this->assertGreaterThan($counts->only($old->modelKeys())->max(), $counts[$new->id]);
        }

        // Các suất trong cùng phòng không chồng giờ nhau
        Showtime::orderBy('start_time')->get()->groupBy('room_id')->each(function ($showtimes) {
            $showtimes->sliding(2)->each(fn ($pair) => $this->assertTrue($pair->first()->end_time->lte($pair->last()->start_time)));
        });
    }

    public function test_genres_without_showing_movies_are_hidden_from_customers(): void
    {
        $visible = Genre::factory()->create(['name' => 'Hành động']);
        $hidden = Genre::factory()->create(['name' => 'Chiến tranh']);
        Movie::factory()->create()->genres()->attach($visible);
        Movie::factory()->create(['status' => 'ended'])->genres()->attach($hidden);

        $this->get(route('movies.index'))
            ->assertOk()
            ->assertSee('Hành động')
            ->assertDontSee('Chiến tranh');
    }
}
