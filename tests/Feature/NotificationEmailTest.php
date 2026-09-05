<?php

namespace Tests\Feature;

use App\Mail\AttendanceUpdated;
use App\Mail\BookingCancelled;
use App\Mail\BookingRequested;
use App\Mail\BookingResponded;
use App\Mail\PaymentPaid;
use App\Mail\PayoutProcessed;
use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CheckinService;
use App\Services\RefundPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Notifikasi email MVP: setiap event kunci mengirim mailable yang tepat
 * ke penerima yang tepat (queue driver sync di test).
 */
class NotificationEmailTest extends TestCase
{
    use RefreshDatabase;

    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

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
            'customer_id' => Customer::where('user_id', $this->ids['customer'])->first()->id,
            'caregiver_id' => $this->ids['caregiver'],
            'start_time' => '2026-09-01 09:00:00',
            'end_time' => '2026-09-01 12:00:00',
            'status' => Booking::STATUS_REQUESTED,
            'total_amount' => 150000,
        ], $overrides));
    }

    private function payBooking(Booking $booking): Payment
    {
        return Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => Payment::generateCode(),
            'amount' => $booking->total_amount,
            'platform_fee' => 0,
            'commission' => 0,
            'method' => 'manual_transfer',
            'payment_type' => 'manual',
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function test_booking_request_notifies_caregiver(): void
    {
        $this->setUpActors();
        $service = app(BookingService::class);
        $caregiver = Caregiver::find($this->ids['caregiver']);
        $customerUser = User::find($this->ids['customer']);

        Schedule::create([
            'caregiver_id' => $caregiver->id,
            'start_time' => '2026-09-10 08:00:00',
            'end_time' => '2026-09-10 16:00:00',
            'status' => 'available',
        ]);

        $booking = $service->createRequest($caregiver, $customerUser, [
            'start_time' => '2026-09-10 09:00:00',
            'end_time' => '2026-09-10 12:00:00',
            'notes' => 'Tolong temani kontrol.',
        ]);

        Mail::assertQueued(BookingRequested::class, 1);
        Mail::assertQueued(BookingRequested::class,
            fn (BookingRequested $m) => $m->hasTo($caregiver->user->email));
    }

    public function test_accept_and_reject_notify_customer(): void
    {
        $this->setUpActors();
        $service = app(BookingService::class);
        $caregiverUser = User::find($this->ids['caregiver_user']);
        $booking = $this->makeBooking();

        $service->accept($booking, $caregiverUser);
        Mail::assertQueued(BookingResponded::class, 1);
        Mail::assertQueued(BookingResponded::class,
            fn (BookingResponded $m) => $m->hasTo($booking->customer->user->email));

        Mail::fake();
        $booking2 = $this->makeBooking(['booking_code' => Booking::generateCode()]);
        $service->reject($booking2, $caregiverUser, 'Tidak tersedia');
        Mail::assertQueued(BookingResponded::class, 1);
        Mail::assertQueued(BookingResponded::class,
            fn (BookingResponded $m) => $m->hasTo($booking2->customer->user->email));
    }

    public function test_settle_payment_notifies_customer_and_caregiver(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking(['status' => Booking::STATUS_ACCEPTED]);
        $customerUser = User::find($this->ids['customer']);

        // Buat payment pending via checkout simulasi lalu lunaskan.
        $this->actingAs($customerUser)->get(route('payment.checkout', $booking));
        $this->assertNotNull($booking->latestPayment());

        $this->actingAs($customerUser)
            ->post(route('payment.settle', $booking))
            ->assertRedirect();

        Mail::assertQueued(PaymentPaid::class, 2);
        Mail::assertQueued(PaymentPaid::class,
            fn (PaymentPaid $m) => $m->hasTo($booking->customer->user->email));
        Mail::assertQueued(PaymentPaid::class,
            fn (PaymentPaid $m) => $m->hasTo($booking->caregiver->user->email));
    }

    public function test_cancel_with_refund_notifies_both_parties(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking(['status' => Booking::STATUS_REQUESTED]);
        $this->payBooking($booking);
        $customer = User::find($this->ids['customer']);

        app(BookingService::class)->cancel($booking, $customer, 'Pasien mendadak sakit');

        Mail::assertQueued(BookingCancelled::class, 2);
        Mail::assertQueued(BookingCancelled::class,
            fn (BookingCancelled $m) => $m->hasTo($booking->customer->user->email));
        Mail::assertQueued(BookingCancelled::class,
            fn (BookingCancelled $m) => $m->hasTo($booking->caregiver->user->email));
    }

    public function test_checkin_and_checkout_notify_customer(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking([
            'status' => Booking::STATUS_CONFIRMED,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addHours(3),
        ]);
        $caregiverUser = User::find($this->ids['caregiver_user']);

        app(CheckinService::class)->checkIn($booking, $caregiverUser);
        Mail::assertQueued(AttendanceUpdated::class, 1);
        Mail::assertQueued(AttendanceUpdated::class,
            fn (AttendanceUpdated $m) => $m->hasTo($booking->customer->user->email));

        app(CheckinService::class)->checkOut($booking->fresh(), $caregiverUser);
        Mail::assertQueued(AttendanceUpdated::class, 2);
    }

    public function test_payout_processed_notifies_caregiver(): void
    {
        $this->setUpActors();
        $booking = $this->makeBooking(['status' => Booking::STATUS_COMPLETED]);
        $this->payBooking($booking);

        $caregiver = Caregiver::find($this->ids['caregiver']);
        [$payout] = app(RefundPayoutService::class)->requestPayout($caregiver, 'Transfer ke BCA ****1234');
        [$ok] = app(RefundPayoutService::class)
            ->processPayout($payout, 'pay', User::find($this->ids['admin']));

        $this->assertTrue($ok);
        Mail::assertQueued(PayoutProcessed::class, 1);
        Mail::assertQueued(PayoutProcessed::class,
            fn (PayoutProcessed $m) => $m->hasTo($caregiver->user->email));
    }
}
