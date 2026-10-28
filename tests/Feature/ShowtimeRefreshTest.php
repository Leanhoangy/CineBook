<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use App\Services\MovieImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_movies_end_automatically_and_are_not_scheduled_after_end_date(): void
    {
        Room::factory()->create();
        $finished = Movie::factory()->create(['release_date' => today()->subWeeks(9), 'end_date' => today()->subDay()]);
        $lastDays = Movie::factory()->create(['release_date' => today()->subWeeks(7), 'end_date' => today()->addDay()]);
        $managedByAdmin = Movie::factory()->create(['release_date' => today()->subYear(), 'end_date' => null]);

        $this->artisan('showtimes:refresh', ['--days' => 3])->assertSuccessful();

        $this->assertSame('ended', $finished->fresh()->status);
        $this->assertSame('now_showing', $lastDays->fresh()->status);
        $this->assertSame('now_showing', $managedByAdmin->fresh()->status);
        $this->assertFalse(Showtime::where('movie_id', $finished->id)->exists());
        $this->assertFalse(Showtime::where('movie_id', $lastDays->id)->whereDate('start_time', '>', today()->addDay())->exists());
        $this->assertTrue(Showtime::where('movie_id', $managedByAdmin->id)->whereDate('start_time', today()->addDays(3))->exists());
    }

    public function test_coming_soon_movie_opens_pre_sale_from_its_release_date(): void
    {
        Room::factory()->create();
        Movie::factory()->create();
        $upcoming = Movie::factory()->comingSoon()->create(['release_date' => today()->addDays(2)]);

        $this->artisan('showtimes:refresh', ['--days' => 3])->assertSuccessful();

        $first = Showtime::where('movie_id', $upcoming->id)->orderBy('start_time')->first();
        $this->assertTrue($first->start_time->isSameDay(today()->addDays(2)));
        $this->assertSame('coming_soon', $upcoming->fresh()->status);

        $this->get(route('movies.show', $upcoming))->assertSee('Đặt vé trước');
        $this->actingAs(User::factory()->create())
            ->post(route('bookings.store', $first), ['seats' => ['A1']])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, $first->bookings()->count());
    }

    public function test_importing_movies_twice_does_not_duplicate_and_estimates_end_dates(): void
    {
        Storage::fake('public');
        $importer = app(MovieImporter::class);

        $first = $importer->import();
        $second = $importer->import();

        $this->assertGreaterThan(0, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame($first['created'], Movie::count());
        $this->assertFalse(Movie::where('status', 'ended')->whereDate('end_date', '>=', today())->exists());
        $this->assertFalse(Movie::where('status', 'now_showing')->whereDate('end_date', '<', today()->addWeeks(2))->exists());
        $this->assertFalse(Movie::whereColumn('end_date', '<', 'release_date')->where('status', '!=', 'ended')->exists());
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

    public function test_coming_soon_movie_shows_when_presale_opens_and_presale_badge(): void
    {
        $later = Movie::factory()->create(['status' => 'coming_soon', 'release_date' => today()->addMonth()]);
        $soon = Movie::factory()->create(['status' => 'coming_soon', 'release_date' => today()->addDays(3)]);
        Showtime::factory()->create(['movie_id' => $soon->id, 'start_time' => today()->addDays(3)->setTime(19, 0)]);

        $this->get(route('movies.show', $later))
            ->assertSee('Mở bán vé từ '.today()->addMonth()->subDays(6)->format('d/m/Y'))
            ->assertDontSee('Đặt vé trước');
        $this->get(route('movies.show', $soon))->assertSee('Đặt vé trước');

        $this->get(route('movies.index', ['trang-thai' => 'coming_soon']))
            ->assertSeeInOrder([$soon->title, 'Đặt vé trước'])
            ->assertSee($later->title);
    }
}
