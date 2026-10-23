<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_accessible(): void
    {
        $this->get('/')->assertOk();
        $this->get(route('movies.index'))->assertOk();
        $this->get(route('schedule.index'))->assertOk();
        $this->get(route('login'))->assertOk();
    }

    public function test_user_can_register_and_always_gets_user_role(): void
    {
        $this->post(route('register'), [
            'name' => 'Trần Thị B',
            'email' => 'b@example.com',
            'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123',
            'role' => 'admin', // cố tình gửi role admin
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'b@example.com', 'role' => 'user']);
    }

    public function test_register_validation_shows_errors(): void
    {
        $this->from(route('register'))->post(route('register'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => '123',
            'password_confirmation' => '456',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_admin_is_redirected_to_dashboard_after_login(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'sai-mat-khau'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_from_admin_area(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_area(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.movies.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_every_admin_page(): void
    {
        $this->seed();
        $admin = User::where('role', 'admin')->first();

        foreach (['dashboard', 'movies.index', 'movies.create', 'movies.trash', 'genres.index', 'rooms.index', 'showtimes.index', 'showtimes.create', 'bookings.index', 'users.index'] as $route) {
            $this->actingAs($admin)->get(route('admin.'.$route))->assertOk();
        }
    }
}
