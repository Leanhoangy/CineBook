<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Combo;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComboTest extends TestCase
{
    use RefreshDatabase;

    private Showtime $showtime;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->showtime = Showtime::factory()->create([
            'room_id' => Room::factory()->create(['total_rows' => 4, 'seats_per_row' => 8, 'vip_rows' => 0]),
            'start_time' => now()->addDay(),
            'price' => 100000,
        ]);
        $this->user = User::factory()->create();
    }

    private function book(array $data)
    {
        return $this->actingAs($this->user)->post(route('bookings.store', $this->showtime), $data);
    }

    public function test_booking_page_lists_only_combos_on_sale(): void
    {
        Combo::factory()->create(['name' => 'Combo Couple', 'price' => 115000]);
        Combo::factory()->inactive()->create(['name' => 'Combo Hết Hàng']);

        $this->actingAs($this->user)->get(route('bookings.create', $this->showtime))
            ->assertSee(['Bắp nước', 'Combo Couple', '115.000 ₫'])
            ->assertDontSee('Combo Hết Hàng');
    }

    public function test_combos_are_added_to_total_and_voucher_only_discounts_tickets(): void
    {
        $couple = Combo::factory()->create(['name' => 'Combo Couple', 'price' => 115000]);
        $drink = Combo::factory()->create(['name' => 'Nước ngọt (L)', 'price' => 35000]);
        Voucher::factory()->create(['code' => 'GIAM10', 'value' => 10, 'max_discount' => null]);

        $this->book(['seats' => ['B1', 'B2'], 'voucher_code' => 'GIAM10', 'combos' => [$couple->id => 1, $drink->id => 2]])
            ->assertSessionHasNoErrors();

        $booking = Booking::with('combos')->firstOrFail();
        $this->assertSame(20000, $booking->discount);                 // 10% của 200.000 tiền vé, không tính bắp nước
        $this->assertSame(200000 - 20000 + 115000 + 70000, $booking->total_price);
        $this->assertSame('1× Combo Couple, 2× Nước ngọt (L)', $booking->combo_list);

        // Đổi giá sau khi bán không làm sai đơn cũ
        $couple->update(['price' => 999000]);
        $this->assertSame(185000, $booking->fresh('combos')->combo_total);

        $this->actingAs($this->user)->get(route('bookings.show', $booking))
            ->assertSee(['Combo Couple × 1', 'Nước ngọt (L) × 2', '70.000 ₫']);
    }

    public function test_inactive_combo_and_too_many_portions_are_rejected(): void
    {
        $off = Combo::factory()->inactive()->create();
        $on = Combo::factory()->create();

        $this->book(['seats' => ['A1'], 'combos' => [$off->id => 1]])->assertSessionHasErrors('combos');
        $this->book(['seats' => ['A1'], 'combos' => [$on->id => 11]])->assertSessionHasErrors('combos.'.$on->id);
        $this->book(['combos' => [$on->id => 1]])->assertSessionHasErrors('seats'); // không mua riêng bắp nước
        $this->assertSame(0, Booking::count());
    }

    public function test_staff_sees_combos_when_checking_in(): void
    {
        $combo = Combo::factory()->create(['name' => 'Combo Gia đình', 'price' => 219000]);
        $this->showtime->update(['start_time' => now()->addMinutes(20)]);
        $this->book(['seats' => ['C1'], 'combos' => [$combo->id => 1]]);

        $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('admin.check-in.lookup', ['code' => Booking::first()->code]))
            ->assertJsonPath('html', fn ($html) => str_contains($html, 'Giao bắp nước:') && str_contains($html, '1× Combo Gia đình'));
    }

    public function test_admin_manages_combos_with_image_and_cannot_delete_sold_combo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.combos.store'), [
            'name' => 'Combo Mới', 'description' => '1 bắp + 1 nước', 'price' => 89000, 'is_active' => 1,
            'image' => UploadedFile::fake()->image('combo.jpg'),
        ])->assertRedirect(route('admin.combos.index'));

        $combo = Combo::where('name', 'Combo Mới')->firstOrFail();
        Storage::disk('public')->assertExists($combo->image);

        $this->actingAs($admin)->post(route('admin.combos.store'), ['name' => '', 'price' => 10])
            ->assertSessionHasErrors(['name', 'price']);

        $this->actingAs($admin)->put(route('admin.combos.update', $combo), ['name' => 'Combo Mới', 'price' => 95000, 'is_active' => 0, 'remove_image' => 1]);
        $this->assertFalse($combo->fresh()->is_active);
        Storage::disk('public')->assertMissing($combo->image);

        $sold = Combo::factory()->create();
        $this->book(['seats' => ['A1'], 'combos' => [$sold->id => 1]]);
        $this->actingAs($admin)->get(route('admin.combos.index'))->assertSee([$sold->name, 'Combo Mới', 'Ngừng bán']);
        $this->actingAs($admin)->delete(route('admin.combos.destroy', $sold))->assertSessionHas('error');
        $this->assertModelExists($sold);

        $this->actingAs($admin)->delete(route('admin.combos.destroy', $combo))->assertSessionHas('success');
        $this->assertModelMissing($combo);
    }
}
