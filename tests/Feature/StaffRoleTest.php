<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffRoleTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = User::factory()->staff()->create(['password' => 'matkhau123']);
    }

    public function test_staff_logs_in_straight_to_check_in_page(): void
    {
        $this->post(route('login'), ['email' => $this->staff->email, 'password' => 'matkhau123'])
            ->assertRedirect(route('admin.check-in.index'));
    }

    public function test_staff_can_only_use_check_in_and_sees_only_that_menu_item(): void
    {
        $this->actingAs($this->staff)->get(route('admin.check-in.index'))
            ->assertOk()
            ->assertSee(['Soát vé', 'Nhân viên soát vé'])
            ->assertDontSee([route('admin.bookings.index'), route('admin.users.index'), route('admin.movies.index')]);

        foreach (['admin.dashboard', 'admin.bookings.index', 'admin.users.index', 'admin.movies.index', 'admin.showtimes.index', 'admin.vouchers.index'] as $route) {
            $this->actingAs($this->staff)->get(route($route))->assertForbidden();
        }
    }

    public function test_staff_checks_in_and_collects_money_but_cannot_open_booking_details(): void
    {
        $booking = Booking::factory()->withSeats(['D1'])->create([
            'showtime_id' => Showtime::factory()->create(['start_time' => now()->addMinutes(20)]),
        ]);

        $this->actingAs($this->staff)->getJson(route('admin.check-in.lookup', ['code' => $booking->code]))
            ->assertOk()
            ->assertJsonPath('html', fn ($html) => ! str_contains($html, 'Xem chi tiết đơn'));

        $this->actingAs($this->staff)->postJson(route('admin.check-in.store', $booking), ['collect' => true])->assertOk();
        $this->assertTrue($booking->fresh()->isCheckedIn());
        $this->assertSame(Booking::PAYMENT_COUNTER, $booking->fresh()->payment_method);

        $this->actingAs($this->staff)->get(route('admin.bookings.show', $booking))->assertForbidden();
        $this->actingAs($this->staff)->patch(route('admin.bookings.update', $booking), ['status' => 'cancelled'])->assertForbidden();
    }

    public function test_staff_cannot_book_tickets_and_customer_cannot_check_in(): void
    {
        $showtime = Showtime::factory()->create(['start_time' => now()->addDay()]);

        $this->actingAs($this->staff)->get(route('bookings.create', $showtime))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.check-in.index'))->assertForbidden();
    }

    public function test_admin_can_create_staff_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Lê Soát Vé',
            'email' => 'nhanvien2@cinebook.test',
            'role' => User::ROLE_STAFF,
            'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123',
        ])->assertRedirect();

        $this->assertSame(User::ROLE_STAFF, User::where('email', 'nhanvien2@cinebook.test')->value('role'));
        $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'staff']))
            ->assertSee(['Lê Soát Vé', 'Nhân viên soát vé']);
    }
}
