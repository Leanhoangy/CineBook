<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use App\Services\SalesReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function sell(Showtime $showtime, array $seats, int $combo = 0, string $status = Booking::STATUS_CONFIRMED): void
    {
        $booking = Booking::factory()->create([
            'showtime_id' => $showtime->id,
            'status' => $status,
            'total_price' => count($seats) * 100000 + $combo,
        ]);
        foreach ($seats as $code) {
            $booking->seats()->create(['seat_code' => $code, 'seat_type' => 'standard', 'price' => 100000]);
        }
        if ($combo) {
            $booking->combos()->create(['name' => 'Combo Couple', 'price' => $combo, 'quantity' => 1]);
        }
    }

    /** 2 suất hôm qua trong phòng 100 ghế: suất tối bán 2 vé + combo, suất sáng bán 1 vé */
    private function scenario(): array
    {
        $room = Room::factory()->create(['total_rows' => 10, 'seats_per_row' => 10]);
        $hot = Movie::factory()->create(['title' => 'Phim Suất Tối']);
        $other = Movie::factory()->create(['title' => 'Phim Suất Sáng']);

        $evening = Showtime::factory()->create(['room_id' => $room->id, 'movie_id' => $hot->id, 'start_time' => today()->subDay()->setTime(19, 0)]);
        $morning = Showtime::factory()->create(['room_id' => $room->id, 'movie_id' => $other->id, 'start_time' => today()->subDay()->setTime(9, 0)]);
        $this->sell($evening, ['E5', 'E6'], combo: 50000);
        $this->sell($evening, ['A1'], status: Booking::STATUS_CANCELLED);              // vé hủy: không tính

        $this->sell($morning, ['C3']);
        $future = Showtime::factory()->create(['room_id' => $room->id, 'start_time' => now()->addDay()]);
        $this->sell($future, ['D1']);                                                  // chưa chiếu: không tính
        $cancelled = Showtime::factory()->create(['room_id' => $room->id, 'start_time' => today()->subDay()->setTime(14, 0)]);
        $cancelled->delete();                                                          // suất bị rạp hủy: không tính

        return [$evening, $morning];
    }

    public function test_report_sums_revenue_occupancy_and_time_slots(): void
    {
        $this->scenario();
        $report = new SalesReport(today()->subDays(6), today());

        $totals = $report->totals();
        $this->assertSame(2, $totals['showtimes']);
        $this->assertSame(3, $totals['seats']);
        $this->assertSame(200, $totals['capacity']);
        $this->assertSame(1.5, $totals['occupancy']);
        $this->assertSame(350000, $totals['revenue']);
        $this->assertSame(50000, $totals['combo_revenue']);
        $this->assertSame(300000, $totals['ticket_revenue']);
        $this->assertSame(50, $totals['combo_rate']); // 1 trong 2 đơn mua kèm

        $slots = $report->bySlot();
        $this->assertSame(1, $slots['morning']['seats']);
        $this->assertSame(2.0, $slots['evening']['occupancy']);
        $this->assertSame('Phim Suất Tối', $slots['evening']['top_movie']['title']);
        $this->assertNull($slots['late']['top_movie']);

        $this->assertSame('Phim Suất Tối', $report->byMovie()->first()['title']);
        $this->assertSame(7, $report->daily()->count());
        $this->assertSame(350000, $report->daily()->firstWhere('label', today()->subDay()->format('d/m'))['value']);
        $this->assertSame(['Combo Couple', 1, 50000], [$report->combos()->first()->name, (int) $report->combos()->first()->quantity, (int) $report->combos()->first()->revenue]);
    }

    public function test_admin_sees_report_page_and_range_is_validated(): void
    {
        $this->scenario();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.reports.index', ['from' => today()->subDays(6)->toDateString(), 'to' => today()->toDateString()]))
            ->assertOk()
            ->assertSee(['Thống kê', '350.000 ₫', '1,5%', 'Tối (17h – 21h)', 'Phim Suất Tối', 'Combo Couple']);

        $this->actingAs($admin)->get(route('admin.reports.index', ['from' => today()->toDateString(), 'to' => today()->subDay()->toDateString()]))
            ->assertSessionHasErrors('to');

        $this->actingAs(User::factory()->staff()->create())->get(route('admin.reports.index'))->assertForbidden();
    }
}
