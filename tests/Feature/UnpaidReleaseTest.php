<?php

namespace Tests\Feature;

use App\Mail\BookingReleased;
use App\Models\Booking;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UnpaidReleaseTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(int $startsInMinutes, array $attributes = []): Booking
    {
        $booking = Booking::factory()->withSeats(['D4', 'D5'])->create($attributes + [
            'showtime_id' => Showtime::factory()->create(['start_time' => now()->addMinutes($startsInMinutes)]),
        ]);
        // Vé đặt từ hôm qua (không còn trong 15 phút ân hạn)
        $booking->forceFill(['created_at' => now()->subDay()])->save();

        return $booking;
    }

    public function test_unpaid_tickets_past_deadline_are_released_and_customer_is_emailed(): void
    {
        Mail::fake();
        $overdue = $this->ticket(25);                                                     // hạn là 5 phút trước
        $notYet = $this->ticket(45);                                                      // còn 15 phút
        $paid = $this->ticket(25, ['paid_at' => now(), 'payment_method' => Booking::PAYMENT_VNPAY]);
        $checkedIn = $this->ticket(10, ['paid_at' => now(), 'payment_method' => Booking::PAYMENT_COUNTER, 'checked_in_at' => now()]);

        $this->artisan('bookings:release-unpaid')->expectsOutputToContain('Đã tự hủy 1 vé')->assertSuccessful();

        $overdue->refresh();
        $this->assertSame(Booking::STATUS_CANCELLED, $overdue->status);
        $this->assertSame(Booking::CANCEL_UNPAID, $overdue->cancel_reason);
        $this->assertNotContains('D4', $overdue->showtime->bookedSeatCodes()); // ghế được mở bán lại
        foreach ([$notYet, $paid, $checkedIn] as $booking) {
            $this->assertTrue($booking->fresh()->isConfirmed());
        }
        Mail::assertSent(BookingReleased::class, fn ($mail) => $mail->hasTo($overdue->user->email));

        $this->actingAs($overdue->user)->get(route('bookings.show', $overdue))
            ->assertSee('Vé đã tự hủy vì chưa thanh toán');
    }

    public function test_last_minute_booking_gets_grace_period_to_pay(): void
    {
        $customer = User::factory()->create();
        $showtime = Showtime::factory()->create(['start_time' => now()->addMinutes(20), 'price' => 80000]);

        $this->actingAs($customer)->post(route('bookings.store', $showtime), ['seats' => ['A1']]);
        $booking = Booking::firstOrFail();

        $this->assertEquals(now()->addMinutes(15)->startOfMinute(), $booking->paymentDeadline()->startOfMinute());
        $this->artisan('bookings:release-unpaid');
        $this->assertTrue($booking->fresh()->isConfirmed());

        $this->travel(16)->minutes();
        $this->artisan('bookings:release-unpaid');
        $this->assertFalse($booking->fresh()->isConfirmed());
    }

    public function test_ticket_shows_payment_deadline_and_online_payment_stops_at_deadline(): void
    {
        config(['services.vnpay.tmn_code' => 'CINETEST', 'services.vnpay.hash_secret' => 'BIMATTHU']);
        $booking = $this->ticket(3 * 60);

        $this->actingAs($booking->user)->get(route('bookings.show', $booking))
            ->assertSee(['Hạn thanh toán', now()->addMinutes(150)->format('H:i d/m/Y'), 'qua VNPay']);

        // Link VNPay hết hạn sau 15 phút, không vượt quá hạn thanh toán của vé
        $url = $this->actingAs($booking->user)->post(route('bookings.pay', $booking))->headers->get('Location');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame(now()->addMinutes(15)->format('YmdHis'), $query['vnp_ExpireDate']);

        $this->travel(151)->minutes();
        $this->assertFalse($booking->fresh()->canBePaidOnline());
    }

    public function test_cancel_reason_is_recorded_for_customer_and_admin(): void
    {
        $byCustomer = $this->ticket(5 * 60);
        $this->actingAs($byCustomer->user)->patch(route('bookings.cancel', $byCustomer));
        $this->assertSame(Booking::CANCEL_BY_CUSTOMER, $byCustomer->fresh()->cancel_reason);

        $byAdmin = $this->ticket(5 * 60);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch(route('admin.bookings.update', $byAdmin), ['status' => Booking::STATUS_CANCELLED]);
        $this->assertSame(Booking::CANCEL_BY_ADMIN, $byAdmin->fresh()->cancel_reason);
        $this->actingAs($admin)->get(route('admin.bookings.show', $byAdmin))->assertSee('Quản trị viên hủy');

        $this->actingAs($admin)->patch(route('admin.bookings.update', $byAdmin), ['status' => Booking::STATUS_CONFIRMED]);
        $this->assertNull($byAdmin->fresh()->cancel_reason);
    }
}
