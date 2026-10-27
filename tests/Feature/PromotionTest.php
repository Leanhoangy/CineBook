<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Showtime;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_price_follows_the_price_table(): void
    {
        $monday = Carbon::parse('2026-11-02'); // thứ Hai

        $this->assertSame(60000, Showtime::standardPriceFor($monday->copy()->setTime(10, 0)));
        $this->assertSame(75000, Showtime::standardPriceFor($monday->copy()->setTime(14, 0)));
        $this->assertSame(85000, Showtime::standardPriceFor($monday->copy()->setTime(19, 0)));
        // Thứ Ba vui vẻ: đồng giá cả ngày
        $this->assertSame(55000, Showtime::standardPriceFor($monday->copy()->addDay()->setTime(10, 0)));
        $this->assertSame(55000, Showtime::standardPriceFor($monday->copy()->addDay()->setTime(21, 0)));
        // Cuối tuần phụ thu
        $this->assertSame(95000, Showtime::standardPriceFor($monday->copy()->addDays(5)->setTime(19, 0)));
    }

    public function test_promotions_page_lists_only_usable_vouchers(): void
    {
        Voucher::factory()->create(['code' => 'DANGCHAY', 'description' => 'Mã đang áp dụng']);
        Voucher::factory()->create(['code' => 'SAPTOI', 'starts_at' => now()->addWeek(), 'expires_at' => now()->addMonth()]);
        Voucher::factory()->expired()->create(['code' => 'HETHAN']);
        Voucher::factory()->create(['code' => 'TAMKHOA', 'is_active' => false]);
        $soldOut = Voucher::factory()->create(['code' => 'HETLUOT', 'usage_limit' => 1]);
        Booking::factory()->create(['voucher_id' => $soldOut->id]);

        $this->get(route('promotions'))
            ->assertOk()
            ->assertSee(['DANGCHAY', 'Mã đang áp dụng', 'SAPTOI', 'Thứ Ba vui vẻ', '55.000 ₫'])
            ->assertDontSee(['HETHAN', 'TAMKHOA', 'HETLUOT']);
    }

    public function test_home_page_shows_promotions(): void
    {
        Voucher::factory()->create(['code' => 'TRANGCHU', 'min_order' => 0]);

        $this->get('/')->assertOk()->assertSee(['Ưu đãi', 'Thứ Ba vui vẻ', 'TRANGCHU']);
    }
}
