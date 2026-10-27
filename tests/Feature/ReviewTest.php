<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Review;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use Database\Seeders\ReviewSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private Movie $movie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->movie = Movie::factory()->create();
    }

    /** Tạo khách có vé xem phim (suất chiếu ở quá khứ hoặc tương lai) */
    private function customerWithTicket(bool $watched = true, string $status = Booking::STATUS_CONFIRMED): User
    {
        $user = User::factory()->create();
        $showtime = Showtime::factory()->create([
            'movie_id' => $this->movie->id,
            'room_id' => Room::factory(),
            'start_time' => $watched ? now()->subDay() : now()->addDay(),
        ]);
        Booking::factory()->for($user)->for($showtime)->create(['status' => $status]);

        return $user;
    }

    public function test_customer_who_watched_the_movie_can_review_and_edit_it(): void
    {
        $user = $this->customerWithTicket();

        $this->actingAs($user)->post(route('reviews.store', $this->movie), ['rating' => 4, 'content' => 'Phim hay'])
            ->assertSessionHas('success');
        $this->actingAs($user)->post(route('reviews.store', $this->movie), ['rating' => 5, 'content' => 'Xem lại còn hay hơn']);

        $this->assertSame(1, Review::count());
        $this->assertDatabaseHas('reviews', ['user_id' => $user->id, 'rating' => 5, 'content' => 'Xem lại còn hay hơn']);
    }

    public function test_customer_cannot_review_before_watching_or_with_cancelled_ticket(): void
    {
        $this->actingAs($this->customerWithTicket(watched: false))
            ->post(route('reviews.store', $this->movie), ['rating' => 5])
            ->assertForbidden();

        $this->actingAs($this->customerWithTicket(status: Booking::STATUS_CANCELLED))
            ->post(route('reviews.store', $this->movie), ['rating' => 5])
            ->assertForbidden();

        $this->assertSame(0, Review::count());
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $this->actingAs($this->customerWithTicket())
            ->post(route('reviews.store', $this->movie), ['rating' => 6])
            ->assertSessionHasErrors('rating');
    }

    public function test_customer_cannot_delete_someone_elses_review(): void
    {
        $review = Review::factory()->for($this->movie)->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('reviews.destroy', $review))
            ->assertForbidden();
    }

    public function test_hidden_reviews_are_not_shown_and_admin_can_toggle_them(): void
    {
        $review = Review::factory()->for($this->movie)->hidden()->create(['content' => 'Quảng cáo spam']);

        $this->get(route('movies.show', $this->movie))->assertOk()->assertDontSee('Quảng cáo spam');

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.reviews.update', $review))
            ->assertSessionHas('success');

        $this->assertFalse($review->fresh()->is_hidden);
        $this->get(route('movies.show', $this->movie))->assertSee('Quảng cáo spam');
    }

    public function test_movie_card_shows_rating_or_new_badge(): void
    {
        $reviewed = Movie::factory()->create(['title' => 'Phim Có Sao']);
        Review::factory()->for($reviewed)->create(['rating' => 4]);
        Movie::factory()->create(['title' => 'Phim Chưa Ai Xem']);

        $html = $this->get(route('movies.index'))->assertOk()->getContent();

        // Phim có đánh giá hiện số sao, phim chưa có đánh giá hiện nhãn "Mới"
        $this->assertMatchesRegularExpression('/Phim Có Sao<\/h3>(?:(?!<\/div>).)*bi-star-fill"><\/i> 4,0/s', $html);
        $this->assertMatchesRegularExpression('/Phim Chưa Ai Xem<\/h3>(?:(?!<\/div>).)*cb-badge-new/s', $html);
    }

    public function test_seeded_comments_match_the_movie_genre(): void
    {
        $animation = Movie::factory()->create();
        $animation->genres()->attach(Genre::factory()->create(['name' => 'Hoạt hình']));
        $showtime = Showtime::factory()->create(['movie_id' => $animation->id, 'room_id' => Room::factory(), 'start_time' => now()->subDay()]);
        Booking::factory()->count(40)->for($showtime)->create();

        $this->seed(ReviewSeeder::class);

        $pool = require database_path('seeders/data/review_comments.php');
        $allowed = array_merge(...array_values($pool['comments']['animation']), ...array_values($pool['general']));
        $contents = Review::whereNotNull('content')->where('is_hidden', false)->pluck('content');

        $this->assertNotEmpty($contents);
        $this->assertEmpty($contents->diff($allowed), 'Phim hoạt hình chỉ được có bình luận hợp với phim hoạt hình');
        $this->assertSame($contents->count(), $contents->unique()->count(), 'Không có 2 bình luận giống hệt nhau trong một phim');
    }
}

