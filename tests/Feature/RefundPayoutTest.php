<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use App\Services\RefundPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Pencatatan refund saat pembatalan dan alur pencairan dana caregiver.
 */
class RefundPayoutTest extends TestCase
{
    use RefreshDatabase;

    private array $ids = [];

    private function setUpActors(): void
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

        $adminUser = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->ids = [
            'customer' => $customerUser->id,
            'caregiver_user' => $caregiverUser->id,
            'caregiver' => $caregiver->id,
            'admin' => $adminUser->id,
        ];
    }

    private function makeBooking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'booking_code' => Booking::generateCode(),
            'customer_id' => $this->ids['customer'] ? Customer::where('user_id', $this->ids['customer'])->first()->id : null,
            'caregiver_id' => $this->ids['caregiver'],
            'start_time' => '2026-09-01 09:00:00',
            'end_time' => '2026-09-01 12:00:00',
            'status' => Booking::STATUS_COMPLETED,
            'total_amount' => 150000,
        ], $overrides));
    }

    private function payBooking(Booking $booking, string $status = Payment::STATUS_PAID): Payment
    {
        return Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => Payment::generateCode(),
            'amount' => $booking->total_amount,
            'platform_fee' => 0,
            'commission' => 0,
            'method' => 'manual_transfer',
            'payment_type' => 'manual',
            'status' => $status,
            'paid_at' => $status === Payment::STATUS_PAID ? now() : null,
        ]);
    }

    public function test_refund_recorded_on_cancellation_with_paid_payment(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking(['status' => Booking::STATUS_CONFIRMED]);
        $this->payBooking($booking);

        [$ok, $message] = app(RefundPayoutService::class)->recordRefund($booking, 100000);

        $this->assertTrue($ok);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'refunded_amount' => 100000,
            'status' => Payment::STATUS_REFUNDED,
        ]);
    }

    public function test_refund_rejected_when_no_paid_payment(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking(['status' => Booking::STATUS_CONFIRMED]);

        [$ok, $message] = app(RefundPayoutService::class)->recordRefund($booking, 100000);

        $this->assertFalse($ok);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_refund_idempotent(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking(['status' => Booking::STATUS_CONFIRMED]);
        $payment = $this->payBooking($booking);

        $svc = app(RefundPayoutService::class);
        $svc->recordRefund($booking, 100000);
        [$ok2, $msg2] = $svc->recordRefund($booking, 100000);

        $this->assertFalse($ok2);
        $this->assertSame(100000.0, (float) $payment->fresh()->refunded_amount);
    }

    public function test_eligible_amount_from_completed_paid_bookings(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking();
        $this->payBooking($booking);

        // Pendapatan: total 150.000 - komisi 20% (30.000) = 120.000
        $this->assertSame(120000.0, Payout::eligibleAmount(Caregiver::find($this->ids['caregiver'])));
    }

    public function test_eligible_amount_excludes_unpaid_or_noncompleted(): void
    {
        $this->setUpActors();
        // Booking selesai tapi belum dibayar
        $this->makeBooking();
        // Booking dibayar tapi belum selesai
        $pending = $this->makeBooking(['status' => Booking::STATUS_CONFIRMED]);
        $this->payBooking($pending);

        $this->assertSame(0.0, Payout::eligibleAmount(Caregiver::find($this->ids['caregiver'])));
    }

    public function test_eligible_amount_subtracts_pending_and_paid_payouts(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking();
        $this->payBooking($booking);

        $caregiver = Caregiver::find($this->ids['caregiver']);

        // Ajukan payout penuh
        Payout::create([
            'caregiver_id' => $caregiver->id,
            'amount' => 120000,
            'status' => Payout::STATUS_PENDING,
        ]);

        $this->assertSame(0.0, Payout::eligibleAmount($caregiver));

        // Admin tolak -> dana kembali eligible
        $payout = Payout::first();
        $payout->update(['status' => Payout::STATUS_REJECTED, 'processed_at' => now(), 'processed_by' => $this->ids['admin']]);
        $this->assertSame(120000.0, Payout::eligibleAmount($caregiver));
    }

    public function test_caregiver_can_request_payout(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking();
        $this->payBooking($booking);

        $response = $this->actingAs(User::find($this->ids['caregiver_user']))
            ->post(route('caregiver.payouts.store'), ['note' => 'BCA ****1234']);

        $response->assertRedirect();
        $this->assertDatabaseHas('payouts', [
            'caregiver_id' => $this->ids['caregiver'],
            'amount' => 120000,
            'status' => Payout::STATUS_PENDING,
        ]);
    }

    public function test_caregiver_cannot_request_when_no_eligible_funds(): void
    {
        $this->setUpActors();

        $response = $this->actingAs(User::find($this->ids['caregiver_user']))
            ->post(route('caregiver.payouts.store'), []);

        $response->assertSessionHasErrors('payout');
    }

    public function test_caregiver_cannot_request_when_pending_exists(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking();
        $this->payBooking($booking);

        $svc = app(RefundPayoutService::class);
        [$payout] = $svc->requestPayout(Caregiver::find($this->ids['caregiver']), null);

        $response = $this->actingAs(User::find($this->ids['caregiver_user']))
            ->post(route('caregiver.payouts.store'), []);

        $response->assertSessionHasErrors('payout');
        $this->assertSame(1, Payout::count());
    }

    public function test_admin_can_mark_payout_as_paid(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking();
        $this->payBooking($booking);

        $payout = Payout::create([
            'caregiver_id' => $this->ids['caregiver'],
            'amount' => 120000,
            'status' => Payout::STATUS_PENDING,
        ]);

        $response = $this->actingAs(User::find($this->ids['admin']))
            ->patch(route('admin.payouts.process', $payout), ['action' => 'pay']);

        $response->assertRedirect();
        $this->assertDatabaseHas('payouts', [
            'id' => $payout->id,
            'status' => Payout::STATUS_PAID,
            'processed_by' => $this->ids['admin'],
        ]);

        // Dana sudah dicairkan, eligible kembali 0
        $this->assertSame(0.0, Payout::eligibleAmount(Caregiver::find($this->ids['caregiver'])));
    }

    public function test_admin_can_reject_payout(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking();
        $this->payBooking($booking);

        $payout = Payout::create([
            'caregiver_id' => $this->ids['caregiver'],
            'amount' => 120000,
            'status' => Payout::STATUS_PENDING,
        ]);

        $response = $this->actingAs(User::find($this->ids['admin']))
            ->patch(route('admin.payouts.process', $payout), ['action' => 'reject', 'admin_note' => 'Rekening tidak valid']);

        $response->assertRedirect();
        $this->assertDatabaseHas('payouts', [
            'id' => $payout->id,
            'status' => Payout::STATUS_REJECTED,
            'admin_note' => 'Rekening tidak valid',
        ]);
        $this->assertSame(120000.0, Payout::eligibleAmount(Caregiver::find($this->ids['caregiver'])));
    }

    public function test_payout_page_visible_to_caregiver_and_admin(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking();
        $this->payBooking($booking);

        $caregiverResponse = $this->actingAs(User::find($this->ids['caregiver_user']))
            ->get(route('caregiver.payouts.index'));
        $caregiverResponse->assertOk();
        $caregiverResponse->assertSee('Dana Dapat Dicairkan');
        $caregiverResponse->assertSee('Rp 120.000');

        $adminResponse = $this->actingAs(User::find($this->ids['admin']))
            ->get(route('admin.payouts.index'));
        $adminResponse->assertOk();
    }

    public function test_payout_cannot_be_processed_twice(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking();
        $this->payBooking($booking);

        $payout = Payout::create([
            'caregiver_id' => $this->ids['caregiver'],
            'amount' => 120000,
            'status' => Payout::STATUS_PENDING,
        ]);

        $svc = app(RefundPayoutService::class);
        [$ok1] = $svc->processPayout($payout, 'pay', User::find($this->ids['admin']));
        $this->assertTrue($ok1);

        // Pemrosesan kedua diabaikan: status tetap paid, dana tidak dicairkan dua kali
        [$ok2, $message] = $svc->processPayout($payout->fresh(), 'pay', User::find($this->ids['admin']));
        $this->assertFalse($ok2);
        $this->assertStringContainsString('sudah diproses', $message);
        $this->assertSame(Payout::STATUS_PAID, $payout->fresh()->status);
        $this->assertSame(0.0, Payout::eligibleAmount(Caregiver::find($this->ids['caregiver'])));
    }

    public function test_payout_request_declined_while_request_lock_is_held(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking();
        $this->payBooking($booking);

        $lock = Cache::lock('payout:request:'.$this->ids['caregiver'], 15);
        $lock->get();

        [$payout, $message] = app(RefundPayoutService::class)
            ->requestPayout(Caregiver::find($this->ids['caregiver']), null);

        $lock->release();

        $this->assertNull($payout);
        $this->assertStringContainsString('diproses', $message);
        $this->assertDatabaseCount('payouts', 0);
    }

    public function test_customer_cannot_access_payout_admin_page(): void
    {
        $this->setUpActors();

        $this->actingAs(User::find($this->ids['customer']))
            ->get(route('admin.payouts.index'))
            ->assertForbidden();
    }
}
