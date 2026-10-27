<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Showtime;
use App\Models\User;
use App\Services\VnpayGateway;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.vnpay.tmn_code' => 'CINETEST', 'services.vnpay.hash_secret' => 'BIMATTHU']);

        $this->user = User::factory()->create();
        $this->booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'showtime_id' => Showtime::factory()->create(['start_time' => now()->addDay()]),
            'total_price' => 170000,
        ]);
    }

    /** Giả lập tham số VNPay gửi về (đã ký bằng chuỗi bí mật) */
    private function vnpayResult(array $overrides = [], ?Booking $booking = null): array
    {
        $booking ??= $this->booking;
        $params = array_merge([
            'vnp_Amount' => $booking->total_price * 100,
            'vnp_BankCode' => 'NCB',
            'vnp_OrderInfo' => 'Thanh toan ve xem phim '.$booking->code,
            'vnp_PayDate' => now()->format('YmdHis'),
            'vnp_ResponseCode' => '00',
            'vnp_TmnCode' => 'CINETEST',
            'vnp_TransactionNo' => '14123456',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => $booking->code.'-120000',
        ], $overrides);
        ksort($params);

        return $params + ['vnp_SecureHash' => hash_hmac('sha512', http_build_query($params), 'BIMATTHU')];
    }

    public function test_ticket_shows_local_qr_and_pay_button_that_redirects_to_signed_vnpay_url(): void
    {
        $this->actingAs($this->user)->get(route('bookings.show', $this->booking))
            ->assertOk()
            ->assertSee('data:image/svg+xml;base64', false) // QR tạo trên server, không gọi dịch vụ ngoài
            ->assertDontSee('api.qrserver.com')
            ->assertSee(['Chưa thanh toán', 'Thanh toán 170.000 ₫ qua VNPay']);

        $url = $this->actingAs($this->user)->post(route('bookings.pay', $this->booking))->headers->get('Location');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?', $url);
        $this->assertSame('17000000', $query['vnp_Amount']);
        $this->assertSame('CINETEST', $query['vnp_TmnCode']);
        $this->assertStringStartsWith($this->booking->code.'-', $query['vnp_TxnRef']);
        $this->assertTrue(app(VnpayGateway::class)->hasValidSignature($query));
    }

    public function test_successful_payment_marks_ticket_paid_and_it_can_no_longer_be_cancelled(): void
    {
        $this->get(route('payments.vnpay.return', $this->vnpayResult()))
            ->assertRedirect(route('bookings.show', $this->booking))
            ->assertSessionHas('success');

        $this->booking->refresh();
        $this->assertTrue($this->booking->isPaid());
        $this->assertSame(Booking::PAYMENT_VNPAY, $this->booking->payment_method);
        $this->assertSame('14123456', $this->booking->payment_ref);

        $this->actingAs($this->user)->get(route('bookings.show', $this->booking))
            ->assertSee(['Đã thanh toán (VNPay)', '14123456'])
            ->assertDontSee('qua VNPay</button>', false);

        $this->actingAs($this->user)->patch(route('bookings.cancel', $this->booking))
            ->assertSessionHas('error', 'Vé đã thanh toán online không hỗ trợ hủy. Vui lòng liên hệ hỗ trợ nếu cần.');
        $this->assertTrue($this->booking->fresh()->isConfirmed());
    }

    public function test_ipn_confirms_payment_only_once_and_rejects_forged_results(): void
    {
        $forged = array_merge($this->vnpayResult(), ['vnp_Amount' => 100]); // sửa số tiền sau khi ký
        $this->getJson(route('payments.vnpay.ipn', $forged))->assertExactJson(['RspCode' => '97', 'Message' => 'Invalid signature']);
        $this->getJson(route('payments.vnpay.ipn', $this->vnpayResult(['vnp_Amount' => 100])))->assertJson(['RspCode' => '04']);
        $this->getJson(route('payments.vnpay.ipn', $this->vnpayResult(['vnp_TxnRef' => 'CBKHONGCO-1'])))->assertJson(['RspCode' => '01']);
        $this->assertFalse($this->booking->fresh()->isPaid());

        $this->getJson(route('payments.vnpay.ipn', $this->vnpayResult()))->assertExactJson(['RspCode' => '00', 'Message' => 'Confirm Success']);
        $this->assertTrue($this->booking->fresh()->isPaid());

        $this->getJson(route('payments.vnpay.ipn', $this->vnpayResult()))->assertJson(['RspCode' => '02']);
    }

    public function test_cancelled_or_failed_payment_keeps_ticket_unpaid(): void
    {
        $this->get(route('payments.vnpay.return', $this->vnpayResult(['vnp_ResponseCode' => '24', 'vnp_TransactionStatus' => '02'])))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Bạn đã hủy thanh toán'));
        $this->get(route('payments.vnpay.return', $this->vnpayResult(['vnp_ResponseCode' => '51', 'vnp_TransactionStatus' => '02'])))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'không thành công'));

        $this->assertFalse($this->booking->fresh()->isPaid());
    }

    public function test_customer_cannot_pay_someone_elses_or_cancelled_ticket(): void
    {
        $this->actingAs(User::factory()->create())->post(route('bookings.pay', $this->booking))
            ->assertSessionHas('error');

        $this->booking->update(['status' => Booking::STATUS_CANCELLED]);
        $this->actingAs($this->user)->post(route('bookings.pay', $this->booking))
            ->assertSessionHas('error');
    }

    public function test_without_vnpay_config_only_counter_payment_is_offered(): void
    {
        config(['services.vnpay.tmn_code' => null]);

        $this->actingAs($this->user)->get(route('bookings.show', $this->booking))
            ->assertSee('Vui lòng thanh toán tại quầy khi nhận vé.')
            ->assertDontSee('qua VNPay</button>', false);
        $this->actingAs($this->user)->post(route('bookings.pay', $this->booking))
            ->assertSessionHas('error');
    }

    public function test_admin_records_counter_payment_and_filters_unpaid_bookings(): void
    {
        $admin = User::factory()->admin()->create();
        $paid = Booking::factory()->paid()->create();

        $this->actingAs($admin)->get(route('admin.bookings.index', ['payment' => 'unpaid']))
            ->assertSee($this->booking->code)
            ->assertDontSee($paid->code);

        $this->actingAs($admin)->get(route('admin.bookings.show', $this->booking))
            ->assertSee(['Chưa thanh toán', 'Đã thu 170.000 ₫']);

        $this->actingAs($admin)->patch(route('admin.bookings.mark-paid', $this->booking))->assertSessionHas('success');
        $this->assertSame(Booking::PAYMENT_COUNTER, $this->booking->fresh()->payment_method);

        $this->actingAs($admin)->get(route('admin.bookings.show', $this->booking))
            ->assertSee('Đã thanh toán (Tại quầy)')
            ->assertDontSee('Đã thu 170.000 ₫');
        $this->actingAs($admin)->patch(route('admin.bookings.mark-paid', $this->booking))->assertSessionHas('error');
    }

    public function test_confirmation_email_embeds_ticket_qr(): void
    {
        $html = (new BookingConfirmed($this->booking))->render();

        $this->assertStringContainsString('Mã QR vé '.$this->booking->code, $html);
        $this->assertStringNotContainsString('api.qrserver.com', $html);
    }
}
