<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Integrasi pembayaran Midtrans Snap + webhook.
 * Di lingkungan test MIDTRANS_SERVER_KEY kosong -> mode simulasi aktif.
 */
class PaymentMidtransTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomerBooking(array $overrides = []): Booking
    {
        $customerUser = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create(['user_id' => $customerUser->id]);
        $caregiverUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);

        $caregiver = Caregiver::create([
            'user_id' => $caregiverUser->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'verification_status' => 'verified',
        ]);

        $day = now()->addDays(3)->format('Y-m-d');
        Schedule::create([
            'caregiver_id' => $caregiver->id,
            'start_time' => $day.' 08:00:00',
            'end_time' => $day.' 16:00:00',
            'status' => 'available',
        ]);

        return Booking::create(array_merge([
            'booking_code' => Booking::generateCode(),
            'customer_id' => $customerUser->customer->id,
            'caregiver_id' => $caregiver->id,
            'start_time' => $day.' 09:00:00',
            'end_time' => $day.' 12:00:00',
            'status' => Booking::STATUS_ACCEPTED,
            'total_amount' => 150000,
        ], $overrides));
    }

    public function test_invoice_shows_pay_button_for_unpaid_accepted_booking(): void
    {
        $booking = $this->makeCustomerBooking();
        $user = User::find($booking->customer->user_id);

        $this->actingAs($user)
            ->get(route('customer.bookings.invoice', $booking))
            ->assertOk()
            ->assertSee('Bayar Sekarang');
    }

    public function test_checkout_creates_pending_payment_in_simulation_mode(): void
    {
        $booking = $this->makeCustomerBooking();
        $user = User::find($booking->customer->user_id);

        $this->actingAs($user)
            ->get(route('payment.checkout', $booking))
            ->assertOk()
            ->assertSee('Mode Simulasi Pembayaran')
            ->assertSee('Bayar Sekarang (simulasi lunas)');

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'status' => 'pending',
            'method' => 'midtrans',
        ]);
    }

    public function test_settle_simulation_marks_payment_paid_and_records_history(): void
    {
        $booking = $this->makeCustomerBooking();
        $user = User::find($booking->customer->user_id);

        // Buat payment pending dulu
        $this->actingAs($user)->get(route('payment.checkout', $booking));
        $payment = $booking->latestPayment();
        $this->assertNotNull($payment);

        $this->actingAs($user)
            ->post(route('payment.settle', $booking))
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'note' => 'Pembayaran diterima: '.$payment->payment_code,
        ]);
    }

    public function test_expire_simulation_marks_payment_failed(): void
    {
        $booking = $this->makeCustomerBooking();
        $user = User::find($booking->customer->user_id);

        $this->actingAs($user)->get(route('payment.checkout', $booking));

        $this->actingAs($user)
            ->post(route('payment.expire', $booking))
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'status' => 'failed',
        ]);
    }

    public function test_checkout_redirects_when_already_paid(): void
    {
        $booking = $this->makeCustomerBooking();
        $user = User::find($booking->customer->user_id);

        $booking->payments()->create([
            'payment_code' => Payment::generateCode(),
            'amount' => $booking->estimateTotal(),
            'method' => 'midtrans',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('payment.checkout', $booking))
            ->assertRedirect(route('customer.bookings.invoice', $booking));
    }

    public function test_simulation_endpoints_blocked_when_midtrans_enabled(): void
    {
        config(['midtrans.enabled' => true]);

        $booking = $this->makeCustomerBooking();
        $user = User::find($booking->customer->user_id);

        $this->actingAs($user)
            ->post(route('payment.settle', $booking))
            ->assertForbidden();
    }

    public function test_non_owner_cannot_access_checkout(): void
    {
        $booking = $this->makeCustomerBooking();
        $outsider = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create(['user_id' => $outsider->id]);

        $this->actingAs($outsider)
            ->get(route('payment.checkout', $booking))
            ->assertForbidden();
    }

    public function test_webhook_settlement_marks_payment_paid(): void
    {
        $booking = $this->makeCustomerBooking();
        $orderId = $booking->invoiceNumber();

        $booking->payments()->create([
            'payment_code' => Payment::generateCode(),
            'amount' => $booking->estimateTotal(),
            'method' => 'midtrans',
            'status' => 'pending',
            'external_id' => $orderId,
        ]);

        $serverKey = 'test-server-key';
        config(['midtrans.enabled' => true, 'midtrans.server_key' => $serverKey]);

        $signature = hash('sha512', $orderId.'200'.'150000.00'.$serverKey);

        $this->postJson(route('payment.webhook'), [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ])->assertOk()->assertJson(['message' => 'OK']);

        $this->assertDatabaseHas('payments', [
            'external_id' => $orderId,
            'status' => 'paid',
            'payment_type' => 'qris',
        ]);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $booking = $this->makeCustomerBooking();
        $orderId = $booking->invoiceNumber();

        $booking->payments()->create([
            'payment_code' => Payment::generateCode(),
            'amount' => $booking->estimateTotal(),
            'method' => 'midtrans',
            'status' => 'pending',
            'external_id' => $orderId,
        ]);

        config(['midtrans.enabled' => true, 'midtrans.server_key' => 'real-server-key']);

        $this->postJson(route('payment.webhook'), [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'signature_key' => hash('sha512', 'wrong-data'),
            'transaction_status' => 'settlement',
        ])->assertStatus(403);
    }

    public function test_webhook_expire_marks_payment_failed(): void
    {
        $booking = $this->makeCustomerBooking();
        $orderId = $booking->invoiceNumber();

        $booking->payments()->create([
            'payment_code' => Payment::generateCode(),
            'amount' => $booking->estimateTotal(),
            'method' => 'midtrans',
            'status' => 'pending',
            'external_id' => $orderId,
        ]);

        config(['midtrans.enabled' => true, 'midtrans.server_key' => 'test-server-key']);

        $signature = hash('sha512', $orderId.'407'.'150000.00'.'test-server-key');

        $this->postJson(route('payment.webhook'), [
            'order_id' => $orderId,
            'status_code' => '407',
            'gross_amount' => '150000.00',
            'signature_key' => $signature,
            'transaction_status' => 'expire',
        ])->assertOk();

        $this->assertDatabaseHas('payments', [
            'external_id' => $orderId,
            'status' => 'failed',
        ]);
    }

    public function test_webhook_is_idempotent_for_repeated_settlement(): void
    {
        $booking = $this->makeCustomerBooking();
        $orderId = $booking->invoiceNumber();

        $payment = $booking->payments()->create([
            'payment_code' => Payment::generateCode(),
            'amount' => $booking->estimateTotal(),
            'method' => 'midtrans',
            'status' => 'pending',
            'external_id' => $orderId,
        ]);

        config(['midtrans.enabled' => true, 'midtrans.server_key' => 'test-server-key']);

        $payload = fn (string $sig) => [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'signature_key' => $sig,
            'transaction_status' => 'settlement',
        ];

        $signature = hash('sha512', $orderId.'200'.'150000.00'.'test-server-key');

        $this->postJson(route('payment.webhook'), $payload($signature))->assertOk();
        $this->postJson(route('payment.webhook'), $payload($signature))->assertOk();

        // Riwayat hanya dicatat sekali
        $this->assertDatabaseCount('booking_status_histories', 1);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
    }

    public function test_settle_simulation_is_rejected_when_already_paid(): void
    {
        $booking = $this->makeCustomerBooking();
        $user = User::find($booking->customer->user_id);

        $this->actingAs($user)->get(route('payment.checkout', $booking));

        $this->actingAs($user)
            ->post(route('payment.settle', $booking))
            ->assertRedirect();

        // Pembayaran kedua ditolak dan tidak menambah riwayat
        $this->actingAs($user)
            ->post(route('payment.settle', $booking))
            ->assertRedirect()
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('booking_status_histories', 1);
    }

    public function test_settle_then_webhook_does_not_duplicate_history(): void
    {
        $booking = $this->makeCustomerBooking();
        $user = User::find($booking->customer->user_id);
        $orderId = $booking->invoiceNumber();

        $this->actingAs($user)->get(route('payment.checkout', $booking));

        // Simulasi lunas lewat tombol lokal
        $this->actingAs($user)
            ->post(route('payment.settle', $booking))
            ->assertRedirect();
        $this->assertDatabaseCount('booking_status_histories', 1);

        // Webhook settlement datang setelahnya -> tidak mencatat riwayat lagi
        config(['midtrans.enabled' => true, 'midtrans.server_key' => 'test-server-key']);
        $signature = hash('sha512', $orderId.'200'.'150000.00'.'test-server-key');

        $this->postJson(route('payment.webhook'), [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
        ])->assertOk();

        $this->assertDatabaseCount('booking_status_histories', 1);
    }

    public function test_webhook_returns_404_for_unknown_order(): void
    {
        config(['midtrans.enabled' => true, 'midtrans.server_key' => 'test-server-key']);

        $orderId = 'INV-20991231-XXXXX';
        $signature = hash('sha512', $orderId.'200'.'100000'.'test-server-key');

        $this->postJson(route('payment.webhook'), [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
        ])->assertStatus(404);
    }

    public function test_snap_token_created_via_api_when_enabled(): void
    {
        Http::fake([
            'api.sandbox.midtrans.com/v2/transaction' => Http::response([
                'status_code' => '201',
                'token' => 'snap-token-abc',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vt token',
            ], 201),
        ]);

        config(['midtrans.enabled' => true, 'midtrans.server_key' => 'sk', 'midtrans.client_key' => 'ck']);

        $service = new MidtransService;
        [$token, $error] = $service->createSnapToken($this->makeCustomerBooking());

        $this->assertSame('snap-token-abc', $token);
        $this->assertSame('', $error);
    }

    public function test_snap_token_failure_returns_error_message(): void
    {
        Http::fake([
            'api.sandbox.midtrans.com/v2/transaction' => Http::response([
                'status_code' => '413',
                'status_message' => 'Order ID sudah ada',
            ], 200),
        ]);

        config(['midtrans.enabled' => true, 'midtrans.server_key' => 'sk', 'midtrans.client_key' => 'ck']);

        $service = new MidtransService;
        [$token, $error] = $service->createSnapToken($this->makeCustomerBooking());

        $this->assertNull($token);
        $this->assertStringContainsString('Order ID sudah ada', $error);
    }
}
