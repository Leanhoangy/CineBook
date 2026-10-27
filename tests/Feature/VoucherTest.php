<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherTest extends TestCase
{
    use RefreshDatabase;

    private Showtime $showtime;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Ghế thường 100.000 ₫ (hàng A–D), không có hàng VIP
        $this->showtime = Showtime::factory()->create([
            'movie_id' => Movie::factory()->create(['status' => 'now_showing']),
            'room_id' => Room::factory()->create(['total_rows' => 4, 'seats_per_row' => 8, 'vip_rows' => 0]),
            'start_time' => now()->addDay(),
            'price' => 100000,
        ]);
        $this->user = User::factory()->create();
    }

    private function book(array $seats, ?string $code, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->user)
            ->post(route('bookings.store', $this->showtime), ['seats' => $seats, 'voucher_code' => $code]);
    }

    public function test_percent_voucher_is_capped_by_max_discount(): void
    {
        $voucher = Voucher::factory()->create(['code' => 'GIAM10', 'value' => 10, 'max_discount' => 25000]);

        $this->book(['A1', 'A2', 'A3'], 'giam10')->assertSessionHasNoErrors(); // nhập chữ thường vẫn nhận

        $booking = Booking::first();
        $this->assertSame($voucher->id, $booking->voucher_id);
        $this->assertSame(25000, $booking->discount);       // 10% của 300.000 = 30.000, giới hạn 25.000
        $this->assertSame(275000, $booking->total_price);
        $this->assertSame(300000, $booking->subtotal);
    }

    public function test_fixed_voucher_requires_minimum_order(): void
    {
        Voucher::factory()->fixed(20000)->create(['code' => 'GIAM20K', 'min_order' => 150000]);

        $this->book(['A1'], 'GIAM20K')->assertSessionHasErrors('voucher_code');
        $this->assertSame(0, Booking::count());

        $this->book(['A1', 'A2'], 'GIAM20K')->assertSessionHasNoErrors();
        $this->assertSame(180000, Booking::first()->total_price);
    }

    public function test_expired_unknown_and_inactive_vouchers_are_rejected(): void
    {
        Voucher::factory()->expired()->create(['code' => 'HETHAN']);
        Voucher::factory()->create(['code' => 'DAKHOA', 'is_active' => false]);

        foreach (['HETHAN', 'DAKHOA', 'KHONGCO'] as $code) {
            $this->book(['A1'], $code)->assertSessionHasErrors('voucher_code');
        }
        $this->assertSame(0, Booking::count());
    }

    public function test_each_customer_can_use_a_voucher_once_and_usage_limit_is_enforced(): void
    {
        Voucher::factory()->create(['code' => 'MOTLAN', 'usage_limit' => 2]);

        $this->book(['A1'], 'MOTLAN')->assertSessionHasNoErrors();
        $this->book(['A2'], 'MOTLAN')->assertSessionHasErrors('voucher_code');      // cùng khách dùng lần 2

        $this->book(['A3'], 'MOTLAN', User::factory()->create())->assertSessionHasNoErrors();
        $this->book(['A4'], 'MOTLAN', User::factory()->create())->assertSessionHasErrors('voucher_code'); // hết lượt

        $this->assertSame(2, Booking::whereNotNull('voucher_id')->count());
    }

    public function test_cancelling_a_ticket_gives_the_voucher_usage_back(): void
    {
        Voucher::factory()->create(['code' => 'HOANLUOT']);
        $this->book(['A1'], 'HOANLUOT');

        Booking::first()->update(['status' => Booking::STATUS_CANCELLED]);

        $this->book(['A2'], 'HOANLUOT')->assertSessionHasNoErrors();
    }

    public function test_ajax_check_returns_discount_or_error(): void
    {
        Voucher::factory()->create(['code' => 'AJAX15', 'value' => 15]);

        $this->actingAs($this->user)
            ->getJson(route('bookings.voucher', [$this->showtime, 'code' => 'ajax15', 'seats' => ['A1', 'A2']]))
            ->assertOk()
            ->assertJson(['valid' => true, 'code' => 'AJAX15', 'discount' => 30000]);

        $this->actingAs($this->user)
            ->getJson(route('bookings.voucher', [$this->showtime, 'code' => 'SAIMA']))
            ->assertStatus(422)
            ->assertJson(['valid' => false]);
    }

    public function test_admin_can_create_voucher_with_validation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.vouchers.store'), [
            'code' => 'sai ma', 'type' => 'percent', 'value' => 150,
        ])->assertSessionHasErrors(['code', 'value']);

        $this->actingAs($admin)->post(route('admin.vouchers.store'), [
            'code' => 'tet2027', 'type' => 'fixed', 'value' => 30000, 'is_active' => 1,
            'expires_at' => now()->addMonth()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('admin.vouchers.index'));

        $this->assertDatabaseHas('vouchers', ['code' => 'TET2027', 'type' => 'fixed', 'value' => 30000]);
    }

    public function test_used_voucher_cannot_be_deleted(): void
    {
        $voucher = Voucher::factory()->create(['code' => 'DADUNG']);
        $this->book(['A1'], 'DADUNG');

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.vouchers.destroy', $voucher))
            ->assertSessionHas('error');

        $this->assertModelExists($voucher);
    }

    public function test_booking_page_suggests_only_vouchers_the_customer_can_still_use(): void
    {
        Voucher::factory()->create(['code' => 'CONDUNG', 'description' => 'Mã còn dùng được']);
        Voucher::factory()->create(['code' => 'DONLON', 'min_order' => 500000]); // chưa đủ đơn vẫn gợi ý, trình duyệt báo "Cần thêm"
        Voucher::factory()->expired()->create(['code' => 'HETHAN']);
        Voucher::factory()->create(['code' => 'SAPTOI', 'starts_at' => now()->addWeek(), 'expires_at' => now()->addMonth()]);
        Voucher::factory()->create(['code' => 'DADUNG']);
        $this->book(['A1'], 'DADUNG');
        Voucher::factory()->create(['code' => 'HETLUOT', 'usage_limit' => 1]); // khách khác đã dùng hết lượt
        $this->book(['A2'], 'HETLUOT', User::factory()->create());
        Voucher::factory()->create(['code' => 'SAPHET', 'usage_limit' => 3]);
        $this->book(['A3'], 'SAPHET', User::factory()->create());

        $this->actingAs($this->user)->get(route('bookings.create', $this->showtime))
            ->assertOk()
            ->assertSee(['Mã dành cho bạn (3)', 'CONDUNG', 'Mã còn dùng được', 'DONLON', 'SAPHET', 'còn 2 lượt'])
            ->assertDontSee(['HETHAN', 'SAPTOI', 'DADUNG', 'HETLUOT']);
    }
}

