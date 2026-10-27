<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_genre_crud(): void
    {
        $this->actingAs($this->admin)->post(route('admin.genres.store'), ['name' => 'Hoạt hình'])
            ->assertRedirect(route('admin.genres.index'))->assertSessionHas('success');
        $genre = Genre::firstWhere('name', 'Hoạt hình');
        $this->assertSame('hoat-hinh', $genre->slug);

        // Không được trùng tên
        $this->actingAs($this->admin)->post(route('admin.genres.store'), ['name' => 'Hoạt hình'])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin)->put(route('admin.genres.update', $genre), ['name' => 'Anime'])
            ->assertSessionHas('success');
        $this->assertSame('Anime', $genre->fresh()->name);

        $this->actingAs($this->admin)->delete(route('admin.genres.destroy', $genre))->assertSessionHas('success');
        $this->assertModelMissing($genre);
    }

    public function test_create_movie_with_poster_upload_and_genres(): void
    {
        Storage::fake('public');
        $genres = Genre::factory()->count(2)->create();

        $this->actingAs($this->admin)->post(route('admin.movies.store'), [
            'title' => 'Phim Mới',
            'description' => 'Một bộ phim hoàn toàn mới với nội dung hấp dẫn.',
            'duration' => 120,
            'release_date' => now()->toDateString(),
            'age_rating' => 'T13',
            'status' => 'now_showing',
            'genres' => $genres->pluck('id')->all(),
            'poster' => UploadedFile::fake()->image('poster.jpg', 400, 600),
        ])->assertSessionHasNoErrors();

        $movie = Movie::firstWhere('title', 'Phim Mới');
        $this->assertSame('phim-moi', $movie->slug);
        $this->assertCount(2, $movie->genres);
        Storage::disk('public')->assertExists($movie->poster);
    }

    public function test_movie_validation_requires_genres_and_valid_duration(): void
    {
        $this->actingAs($this->admin)->post(route('admin.movies.store'), [
            'title' => 'Thiếu dữ liệu',
            'description' => 'ngắn',
            'duration' => 5,
        ])->assertSessionHasErrors(['description', 'duration', 'release_date', 'genres', 'age_rating', 'status']);

        // Ngày kết thúc chiếu không được trước ngày khởi chiếu
        $this->actingAs($this->admin)->post(route('admin.movies.store'), [
            'release_date' => '2026-11-10',
            'end_date' => '2026-11-01',
        ])->assertSessionHasErrors('end_date');
    }

    public function test_movie_soft_delete_and_restore(): void
    {
        $movie = Movie::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.movies.destroy', $movie))->assertSessionHas('success');
        $this->assertSoftDeleted($movie);

        $this->actingAs($this->admin)->patch(route('admin.movies.restore', $movie))->assertSessionHas('success');
        $this->assertNotSoftDeleted($movie);
    }

    public function test_showtime_cannot_overlap_in_same_room(): void
    {
        $room = Room::factory()->create();
        $movie = Movie::factory()->create(['duration' => 120]);
        $start = now()->addDays(2)->setTime(18, 0);

        Showtime::factory()->create(['movie_id' => $movie->id, 'room_id' => $room->id, 'start_time' => $start]);

        // Bắt đầu 19:00 trong khi suất trước kết thúc 20:15 → trùng
        $this->actingAs($this->admin)->post(route('admin.showtimes.store'), [
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => $start->copy()->addHour()->format('Y-m-d H:i'),
            'price' => 80000,
        ])->assertSessionHasErrors('start_time');

        // 20:30 thì hợp lệ
        $this->actingAs($this->admin)->post(route('admin.showtimes.store'), [
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => $start->copy()->addMinutes(150)->format('Y-m-d H:i'),
            'price' => 80000,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, Showtime::count());
        $this->assertEquals($start->copy()->addMinutes(150 + 120 + 15), Showtime::latest('id')->first()->end_time);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin))->assertSessionHas('error');
        $this->assertModelExists($this->admin);
    }
}
