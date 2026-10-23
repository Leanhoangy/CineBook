<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private Showtime $showtime;

    protected function setUp(): void
    {
        parent::setUp();

        // Phòng 5 hàng x 8 ghế, 1 hàng VIP cuối (hàng E)
        $room = Room::factory()->create(['total_rows' => 5, 'seats_per_row' => 8, 'vip_rows' => 1]);
        $movie = Movie::factory()->create(['status' => 'now_showing', 'duration' => 100]);
        $this->showtime = Showtime::factory()->create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addDay()->setTime(19, 0),
            'price' => 80000,
        ]);
    }

    public function test_guest_must_login_to_book(): void
    {
        $this->get(route('bookings.create', $this->showtime))->assertRedirect(route('login'));
    }

    public function test_customer_can_book_seats_and_vip_surcharge_is_applied(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('bookings.store', $this->showtime), [
            'seats' => ['A1', 'E3'],
        ]);

        $booking = Booking::first();
        $response->assertRedirect(route('bookings.show', $booking))->assertSessionHas('success');

        $this->assertSame($user->id, $booking->user_id);
        $this->assertSame(80000 + 80000 + config('cinema.vip_surcharge'), $booking->total_price);
        $this->assertDatabaseHas('booking_seats', ['booking_id' => $booking->id, 'seat_code' => 'E3', 'seat_type' => 'vip']);
    }

    public function test_cannot_book_a_seat_that_is_already_taken(): void
    {
        Booking::factory()->for($this->showtime)->withSeats(['B2'])->create();

        $this->actingAs(User::factory()->create())
            ->post(route('bookings.store', $this->showtime), ['seats' => ['B1', 'B2']])
            ->assertSessionHasErrors('seats');

        $this->assertSame(1, Booking::count());
    }

    public function test_seat_of_cancelled_booking_can_be_booked_again(): void
    {
        Booking::factory()->for($this->showtime)->cancelled()->withSeats(['C4'])->create();

        $this->actingAs(User::factory()->create())
            ->post(route('bookings.store', $this->showtime), ['seats' => ['C4']])
            ->assertSessionHasNoErrors();
    }

    public function test_rejects_seats_outside_room_layout_and_too_many_seats(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('bookings.store', $this->showtime), ['seats' => ['Z9']])
            ->assertSessionHasErrors('seats');

        $this->actingAs($user)->post(route('bookings.store', $this->showtime), ['seats' => []])
            ->assertSessionHasErrors('seats');

        $tooMany = array_map(fn ($n) => 'A'.$n, range(1, 8));
        $tooMany[] = 'B1';
        $this->actingAs($user)->post(route('bookings.store', $this->showtime), ['seats' => $tooMany])
            ->assertSessionHasErrors('seats');
    }

    public function test_cannot_book_a_past_showtime(): void
    {
        $this->showtime->update(['start_time' => now()->subHour()]);

        $this->actingAs(User::factory()->create())
            ->post(route('bookings.store', $this->showtime), ['seats' => ['A1']])
            ->assertSessionHasErrors('seats');
    }

    public function test_seat_status_api_returns_booked_seats(): void
    {
        Booking::factory()->for($this->showtime)->withSeats(['D5', 'D6'])->create();

        $this->getJson(route('showtimes.seats', $this->showtime))
            ->assertOk()
            ->assertJsonPath('booked', ['D5', 'D6']);
    }

    public function test_customer_can_cancel_own_booking_but_not_others(): void
    {
        $owner = User::factory()->create();
        $booking = Booking::factory()->for($owner)->for($this->showtime)->withSeats(['A1'])->create();

        $this->actingAs(User::factory()->create())->get(route('bookings.show', $booking))->assertForbidden();

        $this->actingAs($owner)->patch(route('bookings.cancel', $booking))->assertSessionHas('success');
        $this->assertSame(Booking::STATUS_CANCELLED, $booking->fresh()->status);
    }

    public function test_admin_cannot_use_customer_booking_routes(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('bookings.create', $this->showtime))
            ->assertForbidden();
    }
}
