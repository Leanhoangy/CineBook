<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    /** Vé của suất chiếu bắt đầu sau $minutes phút */
    private function ticket(int $minutes = 30, array $attributes = []): Booking
    {
        return Booking::factory()->withSeats(['C5', 'C6'])->create($attributes + [
            'showtime_id' => Showtime::factory()->create(['start_time' => now()->addMinutes($minutes)]),
        ]);
    }

    private function lookup(string $code)
    {
        return $this->actingAs($this->admin)->getJson(route('admin.check-in.lookup', ['code' => $code]));
    }

    private function checkIn(Booking $booking, bool $collect = false)
    {
        return $this->actingAs($this->admin)->postJson(route('admin.check-in.store', $booking), ['collect' => $collect]);
    }

    public function test_only_admin_can_open_check_in_page(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.check-in.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.check-in.index'))
            ->assertOk()
            ->assertSee(['Soát vé', 'Bật camera', 'Đã soát hôm nay']);
    }

    public function test_board_lists_showtimes_open_for_check_in_and_opening_soon(): void
    {
        $open = $this->ticket(30, ['paid_at' => now(), 'payment_method' => Booking::PAYMENT_VNPAY]); // mở soát từ 30 phút trước
        $open->update(['checked_in_at' => now()]);
        $this->ticket(30, ['showtime_id' => $open->showtime_id]);                                    // vé thứ 2, chưa trả tiền
        $soon = $this->ticket(100);                                                                   // mở soát sau 40 phút
        $later = $this->ticket(300);                                                                  // còn lâu
        Showtime::factory()->create(['start_time' => now()->addMinutes(20)]);                        // chưa bán vé nào: không hiện

        $this->actingAs($this->admin)->get(route('admin.check-in.index'))
            ->assertSee(['Suất đang soát vé', $open->showtime->movie->title, '1/2', '1 vé chưa thanh toán', 'Bắt đầu sau 30 phút'])
            ->assertSee(['Sắp mở soát', 'Mở '.$soon->showtime->checkInOpensAt()->format('H:i')])
            ->assertDontSee($later->showtime->movie->title)
            ->assertSee('title="1 suất đang trong giờ soát vé"', false); // số trên menu

        $this->actingAs($this->admin)->getJson(route('admin.check-in.showtimes'))
            ->assertOk()
            ->assertJsonCount(1, 'open')
            ->assertJsonPath('open.0.id', $open->showtime_id);
    }

    public function test_paid_ticket_is_let_in_once(): void
    {
        $booking = $this->ticket(attributes: ['paid_at' => now(), 'payment_method' => Booking::PAYMENT_VNPAY]);

        // Mã gõ tay chữ thường, có khoảng trắng vẫn tìm được
        $this->lookup(' '.strtolower($booking->code).' ')
            ->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonPath('html', fn ($html) => str_contains($html, 'Vé hợp lệ') && str_contains($html, 'Cho vào'));

        $this->checkIn($booking)
            ->assertOk()
            ->assertJson(['ok' => true, 'count' => 1])
            ->assertJsonPath('html', fn ($html) => str_contains($html, 'Mời vào'));
        $this->assertTrue($booking->fresh()->isCheckedIn());

        // Quét lại lần 2 (VD: chụp màn hình vé đưa cho người khác) bị chặn
        $this->lookup($booking->code)->assertJson(['ok' => false])
            ->assertJsonPath('html', fn ($html) => str_contains($html, 'Không cho vào lần thứ hai'));
        $this->checkIn($booking)->assertStatus(422);

        $this->actingAs($this->admin)->get(route('admin.check-in.index'))->assertSee([$booking->code, '1 vé']);
    }

    public function test_unpaid_ticket_requires_collecting_money_first(): void
    {
        $booking = $this->ticket();

        $this->lookup($booking->code)->assertJsonPath('html', fn ($html) => str_contains($html, 'Vé chưa thanh toán'));
        $this->checkIn($booking)->assertStatus(422);
        $this->assertFalse($booking->fresh()->isCheckedIn());

        $this->checkIn($booking, collect: true)->assertOk();
        $booking->refresh();
        $this->assertTrue($booking->isCheckedIn());
        $this->assertSame(Booking::PAYMENT_COUNTER, $booking->payment_method);
    }

    public function test_cancelled_too_early_ended_or_unknown_tickets_are_rejected(): void
    {
        $cancelled = $this->ticket(attributes: ['status' => Booking::STATUS_CANCELLED]);
        $tomorrow = $this->ticket(24 * 60);
        $ended = $this->ticket(-300);

        $this->lookup($cancelled->code)->assertJsonPath('html', fn ($html) => str_contains($html, 'Vé đã bị hủy'));
        $this->lookup($tomorrow->code)->assertJsonPath('html', fn ($html) => str_contains($html, 'Chưa tới giờ soát vé'));
        $this->lookup($ended->code)->assertJsonPath('html', fn ($html) => str_contains($html, 'Suất chiếu đã kết thúc'));
        $this->lookup('CBKHONGCO')->assertNotFound()->assertJsonPath('html', fn ($html) => str_contains($html, 'Không tìm thấy vé'));

        foreach ([$cancelled, $tomorrow, $ended] as $booking) {
            $this->checkIn($booking, collect: true)->assertStatus(422);
            $this->assertFalse($booking->fresh()->isCheckedIn());
        }
    }

    public function test_customer_ticket_shows_check_in_time(): void
    {
        $booking = $this->ticket(attributes: ['paid_at' => now(), 'payment_method' => Booking::PAYMENT_VNPAY, 'checked_in_at' => now()]);

        $this->actingAs($booking->user)->get(route('bookings.show', $booking))
            ->assertSee('Đã vào rạp lúc '.now()->format('H:i d/m'));
    }

    public function test_home_page_reminds_customer_of_todays_ticket_with_qr_link(): void
    {
        $this->travelTo(today()->setTime(15, 0));
        $customer = User::factory()->create();
        $today = $this->ticket(4 * 60, ['user_id' => $customer->id]);       // 19:00 hôm nay
        $this->ticket(24 * 60, ['user_id' => $customer->id]);               // ngày mai: không nhắc
        $this->ticket(60, ['user_id' => $customer->id, 'status' => Booking::STATUS_CANCELLED]);
        $this->ticket(30, ['user_id' => $customer->id, 'checked_in_at' => now()]); // đã vào rạp

        $this->actingAs($customer)->get(route('home'))
            ->assertSee([$today->showtime->movie->title, '19:00', 'Ghế C5, C6', 'chưa thanh toán', 'Mở mã QR'])
            ->assertSee(route('bookings.show', $today).'#qr', false)
            ->assertDontSee('vé khác hôm nay');

        $this->actingAs($this->admin)->get(route('home'))->assertDontSee('Mở mã QR');
        auth()->logout();
        $this->get(route('home'))->assertDontSee('Mở mã QR');
    }

    public function test_ticket_qr_can_be_enlarged_until_checked_in(): void
    {
        $booking = $this->ticket();

        $this->actingAs($booking->user)->get(route('bookings.show', $booking))
            ->assertSee(['Bấm để phóng to', 'id="qr"', 'Tăng độ sáng màn hình'], false);

        $booking->update(['checked_in_at' => now()]);
        $this->actingAs($booking->user)->get(route('bookings.show', $booking))
            ->assertDontSee(['Bấm để phóng to', 'id="qr"'], false);
    }
}
