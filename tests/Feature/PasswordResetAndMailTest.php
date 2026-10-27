<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetAndMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_reset_password_via_emailed_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'khach@example.com']);

        $this->post(route('password.email'), ['email' => 'khach@example.com'])->assertSessionHas('success');

        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'khach@example.com',
            'password' => 'matkhaumoi123',
            'password_confirmation' => 'matkhaumoi123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('matkhaumoi123', $user->fresh()->password));
    }

    public function test_unknown_email_does_not_reveal_whether_account_exists(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'khongco@example.com'])
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => 'sai-token',
            'email' => $user->email,
            'password' => 'matkhaumoi123',
            'password_confirmation' => 'matkhaumoi123',
        ])->assertSessionHasErrors('email');
    }

    public function test_customer_receives_ticket_email_after_booking(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $showtime = Showtime::factory()->create([
            'movie_id' => Movie::factory()->create(['status' => 'now_showing']),
            'room_id' => Room::factory()->create(),
            'start_time' => now()->addDay(),
        ]);

        $this->actingAs($user)->post(route('bookings.store', $showtime), ['seats' => ['A1']]);

        Mail::assertSent(BookingConfirmed::class, fn ($mail) => $mail->hasTo($user->email)
            && $mail->booking->seat_list === 'A1');
    }
}
