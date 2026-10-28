<?php

namespace Tests\Feature;

use App\Mail\BookingCancelledByCinema;
use App\Models\Booking;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.vnpay.tmn_code' => 'CINETEST', 'services.vnpay.hash_secret' => 'BIMATTHU']);
        Mail::fake();
        $this->admin = User::factory()->admin()->create();
    }

    private function vnpayTicket(array $attributes = []): Booking
    {
        return Booking::factory()->withSeats(['E5', 'E6'])->create($attributes + [
            'showtime_id' => Showtime::factory()->create(['start_time' => now()->addDay()]),
            'paid_at' => now()->setTime(9, 30),
            'payment_method' => Booking::PAYMENT_VNPAY,
            'payment_ref' => '14123456',
            'payment_txn_ref' => 'CBTEST01-093000',
        ]);
    }

    private function fakeVnpay(string $code = '00'): void
    {
        Http::fake(['sandbox.vnpayment.vn/*' => Http::response([
            'vnp_ResponseCode' => $code, 'vnp_Message' => 'Ket qua', 'vnp_TransactionNo' => '14999999',
        ])]);
    }

    public function test_admin_refunds_vnpay_ticket_via_signed_api_call(): void
    {
        $this->fakeVnpay();
        $booking = $this->vnpayTicket();

        $this->actingAs($this->admin)->post(route('admin.bookings.refund', $booking), ['method' => 'vnpay'])
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertTrue($booking->isRefunded());
        $this->assertSame(Booking::STATUS_CANCELLED, $booking->status);
        $this->assertSame(Booking::CANCEL_BY_ADMIN, $booking->cancel_reason);
        $this->assertSame('14999999', $booking->refund_ref);
        $this->assertSame('Đã hoàn tiền', $booking->payment_label);
        $this->assertNotContains('E5', $booking->showtime->bookedSeatCodes()); // ghế được mở bán lại
        Mail::assertSent(BookingCancelledByCinema::class, fn ($m) => $m->booking->is($booking));

        // Gửi đúng giao dịch gốc, đúng số tiền, chữ ký theo thứ tự quy định của VNPay
        Http::assertSent(function (Request $request) use ($booking) {
            $data = $request->data();
            $fields = ['vnp_RequestId', 'vnp_Version', 'vnp_Command', 'vnp_TmnCode', 'vnp_TransactionType', 'vnp_TxnRef', 'vnp_Amount',
                'vnp_TransactionNo', 'vnp_TransactionDate', 'vnp_CreateBy', 'vnp_CreateDate', 'vnp_IpAddr', 'vnp_OrderInfo'];
            $hash = hash_hmac('sha512', implode('|', array_map(fn ($f) => $data[$f], $fields)), 'BIMATTHU');

            return $data['vnp_Command'] === 'refund'
                && $data['vnp_TxnRef'] === 'CBTEST01-093000'
                && $data['vnp_Amount'] === $booking->total_price * 100
                && $data['vnp_TransactionDate'] === now()->setTime(9, 30)->format('YmdHis')
                && $data['vnp_SecureHash'] === $hash;
        });

        // Không hoàn lần 2, không khôi phục được vé đã hoàn
        $this->actingAs($this->admin)->post(route('admin.bookings.refund', $booking), ['method' => 'vnpay'])->assertSessionHas('error');
        $this->actingAs($this->admin)->patch(route('admin.bookings.update', $booking), ['status' => 'confirmed'])->assertSessionHas('error');
        Http::assertSentCount(1);
    }

    public function test_vnpay_rejection_keeps_ticket_unchanged(): void
    {
        $this->fakeVnpay('91');
        $booking = $this->vnpayTicket();

        $this->actingAs($this->admin)->post(route('admin.bookings.refund', $booking), ['method' => 'vnpay'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'không tìm thấy giao dịch'));

        $booking->refresh();
        $this->assertFalse($booking->isRefunded());
        $this->assertTrue($booking->isConfirmed());
        Mail::assertNothingSent();
    }

    public function test_counter_payment_is_refunded_manually_with_a_note(): void
    {
        Http::fake();
        $booking = $this->vnpayTicket(['payment_method' => Booking::PAYMENT_COUNTER, 'payment_ref' => null, 'payment_txn_ref' => null]);

        $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking))
            ->assertSee(['Hủy vé &amp; hoàn tiền', 'Trả tiền mặt tại quầy'], false)
            ->assertDontSee('qua VNPay</button>', false);

        $this->actingAs($this->admin)->post(route('admin.bookings.refund', $booking), ['method' => 'manual'])
            ->assertSessionHasErrors('refund_note');

        $this->actingAs($this->admin)->post(route('admin.bookings.refund', $booking), ['method' => 'manual', 'refund_note' => 'Trả tiền mặt tại quầy'])
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(Booking::REFUND_MANUAL, $booking->refund_method);
        $this->assertSame('Trả tiền mặt tại quầy', $booking->refund_ref);
        Http::assertNothingSent();

        // Vé đã có giao dịch tiền không được xóa
        $this->actingAs($this->admin)->delete(route('admin.bookings.destroy', $booking))->assertSessionHas('error');
        $this->assertModelExists($booking);
    }

    public function test_payment_for_already_cancelled_ticket_is_recorded_as_pending_refund(): void
    {
        $booking = Booking::factory()->withSeats(['A1'])->create([
            'showtime_id' => Showtime::factory()->create(['start_time' => now()->addHour()]),
            'status' => Booking::STATUS_CANCELLED,
            'cancel_reason' => Booking::CANCEL_UNPAID,
        ]);
        $params = [
            'vnp_Amount' => $booking->total_price * 100, 'vnp_PayDate' => '20261028101500', 'vnp_ResponseCode' => '00',
            'vnp_TmnCode' => 'CINETEST', 'vnp_TransactionNo' => '14777777', 'vnp_TransactionStatus' => '00', 'vnp_TxnRef' => $booking->code.'-101000',
        ];
        ksort($params);
        $params['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($params), 'BIMATTHU');

        $this->getJson(route('payments.vnpay.ipn', $params))->assertJson(['RspCode' => '00']);

        $booking->refresh();
        $this->assertTrue($booking->needsRefund());
        $this->assertSame($booking->code.'-101000', $booking->payment_txn_ref);
        $this->assertSame('2026-10-28 10:15:00', $booking->paid_at->format('Y-m-d H:i:s'));

        $this->actingAs($this->admin)->get(route('admin.bookings.index', ['payment' => 'refund_pending']))
            ->assertSee($booking->code);
        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertSee('cần hoàn tiền');
        $this->actingAs($booking->user)->get(route('bookings.show', $booking))
            ->assertSee(['Chờ hoàn tiền', 'Rạp sẽ hoàn lại toàn bộ số tiền']);
    }

    public function test_cancelling_showtime_cancels_all_tickets_and_refunds_paid_ones(): void
    {
        $this->fakeVnpay();
        $showtime = Showtime::factory()->create(['start_time' => now()->addHours(3)]);
        $vnpay = $this->vnpayTicket(['showtime_id' => $showtime->id]);
        $counter = Booking::factory()->withSeats(['F1'])->paid(Booking::PAYMENT_COUNTER)->create(['showtime_id' => $showtime->id]);
        $unpaid = Booking::factory()->withSeats(['F2'])->create(['showtime_id' => $showtime->id]);

        $this->actingAs($this->admin)->post(route('admin.showtimes.cancel', $showtime))
            ->assertSessionHas('success', fn ($m) => str_contains($m, '3 vé bị hủy') && str_contains($m, '1 vé đã hoàn tiền'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Còn 1 vé'));

        $this->assertSoftDeleted($showtime);
        $this->assertTrue($vnpay->fresh()->isRefunded());
        $this->assertTrue($counter->fresh()->needsRefund());
        $this->assertSame(Booking::CANCEL_SHOWTIME, $unpaid->fresh()->cancel_reason);
        Mail::assertSent(BookingCancelledByCinema::class, 3);

        // Suất đã hủy biến mất khỏi trang khách nhưng vé cũ vẫn xem được
        $this->get(route('movies.show', $showtime->movie))->assertDontSee(route('bookings.create', $showtime));
        $this->actingAs($unpaid->user)->get(route('bookings.show', $unpaid))
            ->assertOk()->assertSee('rạp đã hủy suất chiếu này');
        $this->actingAs($this->admin)->get(route('admin.showtimes.index'))->assertSee('Đã hủy suất');
    }

    public function test_customer_cannot_refund(): void
    {
        $booking = $this->vnpayTicket();

        $this->actingAs($booking->user)->post(route('admin.bookings.refund', $booking), ['method' => 'vnpay'])->assertForbidden();
        $this->assertFalse($booking->fresh()->isRefunded());
    }
}
