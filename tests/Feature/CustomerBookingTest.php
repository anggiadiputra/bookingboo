<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CustomerBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $customerUser;

    private Customer $customer;

    private User $caregiverUser;

    private Caregiver $caregiver;

    private string $day;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);
        $this->customer = Customer::create(['user_id' => $this->customerUser->id]);

        $this->caregiverUser = User::factory()->create([
            'role' => 'caregiver',
            'status' => 'active',
            'approved_at' => now(),
        ]);
        $this->caregiver = Caregiver::create([
            'user_id' => $this->caregiverUser->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'rating' => 4.8,
            'verification_status' => 'verified',
        ]);

        $this->day = now()->addDays(3)->format('Y-m-d');
    }

    private function makeSchedule(string $start = '08:00', string $end = '16:00', string $status = Schedule::STATUS_AVAILABLE, ?string $date = null): Schedule
    {
        $day = $date ?? $this->day;

        return Schedule::create([
            'caregiver_id' => $this->caregiver->id,
            'start_time' => $day.' '.$start.':00',
            'end_time' => $day.' '.$end.':00',
            'status' => $status,
        ]);
    }

    public function test_customer_can_view_booking_form(): void
    {
        $this->actingAs($this->customerUser)
            ->get(route('customer.bookings.create', $this->caregiver))
            ->assertOk()
            ->assertSee($this->caregiver->user->name);
    }

    public function test_customer_can_submit_booking_request(): void
    {
        $this->makeSchedule();

        $response = $this->actingAs($this->customerUser)->post(route('customer.bookings.store', $this->caregiver), [
            'start_time' => $this->day.'T09:00',
            'end_time' => $this->day.'T12:00',
            'location' => 'Jl. Melati No. 1',
            'needs' => 'Menemani kontrol ke RS',
        ]);

        $booking = Booking::where('customer_id', $this->customer->id)->first();

        $response->assertRedirect(route('customer.bookings.show', $booking));
        $this->assertDatabaseCount('bookings', 1);
        $this->assertEquals(Booking::STATUS_REQUESTED, $booking->status);
        $this->assertStringStartsWith('BB-', $booking->booking_code);
        // Estimasi: 3 jam x 50.000
        $this->assertEquals(150000, $booking->estimateTotal());
        // Riwayat status tercatat
        $this->assertDatabaseCount('booking_status_histories', 1);
        // Jadwal tercakup otomatis menjadi booked
        $this->assertEquals(Schedule::STATUS_BOOKED, $booking->fresh()->caregiver->schedules()->first()->status);
    }

    public function test_booking_outside_schedule_is_rejected(): void
    {
        $this->makeSchedule('08:00', '12:00');

        $this->actingAs($this->customerUser)->post(route('customer.bookings.store', $this->caregiver), [
            'start_time' => $this->day.'T13:00',
            'end_time' => $this->day.'T15:00',
        ])->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_overlapping_booking_is_rejected(): void
    {
        $this->makeSchedule();

        $this->actingAs($this->customerUser)->post(route('customer.bookings.store', $this->caregiver), [
            'start_time' => $this->day.'T09:00',
            'end_time' => $this->day.'T11:00',
        ])->assertSessionHasNoErrors();

        // Booking kedua yang tumpang tindih ditolak
        $this->actingAs($this->customerUser)->post(route('customer.bookings.store', $this->caregiver), [
            'start_time' => $this->day.'T10:00',
            'end_time' => $this->day.'T12:00',
        ])->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_unverified_caregiver_cannot_be_booked(): void
    {
        $this->caregiver->update(['verification_status' => 'pending']);

        $this->actingAs($this->customerUser)
            ->post(route('customer.bookings.store', $this->caregiver), [
                'start_time' => $this->day.'T09:00',
                'end_time' => $this->day.'T11:00',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_past_start_time_is_rejected(): void
    {
        $this->makeSchedule();

        $past = now()->subDay()->format('Y-m-d');

        $this->actingAs($this->customerUser)->post(route('customer.bookings.store', $this->caregiver), [
            'start_time' => $past.'T09:00',
            'end_time' => $past.'T11:00',
        ])->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_caregiver_can_accept_request(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);

        $this->actingAs($this->caregiverUser)
            ->patch(route('caregiver.bookings.accept', $booking))
            ->assertSessionHas('status');

        $this->assertEquals(Booking::STATUS_ACCEPTED, $booking->fresh()->status);
        $this->assertDatabaseCount('booking_status_histories', 2);
    }

    public function test_caregiver_can_reject_request_and_schedule_released(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);

        $this->actingAs($this->caregiverUser)
            ->patch(route('caregiver.bookings.reject', $booking), ['reason' => 'Jadwal bentrok'])
            ->assertSessionHas('status');

        $booking = $booking->fresh();
        $this->assertEquals(Booking::STATUS_REJECTED, $booking->status);
        $this->assertEquals('Jadwal bentrok', $booking->cancellation_reason);
        // Jadwal yang sempat dibooking dikembalikan ke available
        $this->assertEquals(Schedule::STATUS_AVAILABLE, $this->caregiver->schedules()->first()->status);
        // Setelah ditolak, slot bisa diajukan lagi
        $this->actingAs($this->customerUser)->post(route('customer.bookings.store', $this->caregiver), [
            'start_time' => $this->day.'T09:00',
            'end_time' => $this->day.'T11:00',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_cannot_accept_non_requested_booking(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        $booking->update(['status' => Booking::STATUS_ACCEPTED]);

        $this->actingAs($this->caregiverUser)
            ->patch(route('caregiver.bookings.accept', $booking))
            ->assertSessionHasErrors('booking');

        $this->assertEquals(Booking::STATUS_ACCEPTED, $booking->fresh()->status);
    }

    public function test_customer_can_cancel_requested_booking(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);

        $this->actingAs($this->customerUser)
            ->patch(route('customer.bookings.cancel', $booking))
            ->assertRedirect();

        $booking = $booking->fresh();
        $this->assertEquals(Booking::STATUS_CANCELLED, $booking->status);
        $this->assertNotNull($booking->cancelled_at);
        // Jadwal dilepas kembali
        $this->assertEquals(Schedule::STATUS_AVAILABLE, $this->caregiver->schedules()->first()->status);
        // Perubahan status tercatat di riwayat
        $this->assertTrue($booking->statusHistories()
            ->where('to_status', Booking::STATUS_CANCELLED)
            ->exists());
    }

    public function test_customer_cannot_cancel_other_customer_booking(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);

        $other = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create(['user_id' => $other->id]);

        $this->actingAs($other)
            ->patch(route('customer.bookings.cancel', $booking))
            ->assertForbidden();

        $this->assertEquals(Booking::STATUS_REQUESTED, $booking->fresh()->status);
    }

    public function test_customer_sees_only_their_bookings(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);

        $other = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create(['user_id' => $other->id]);

        $this->actingAs($other)
            ->get(route('customer.bookings.show', $booking))
            ->assertForbidden();

        $this->actingAs($this->customerUser)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertSee($booking->booking_code);
    }

    public function test_caregiver_sees_incoming_bookings(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);

        $this->actingAs($this->caregiverUser)
            ->get(route('caregiver.bookings.index'))
            ->assertOk()
            ->assertSee($booking->booking_code);

        $this->actingAs($this->caregiverUser)
            ->get(route('caregiver.bookings.show', $booking))
            ->assertOk()
            ->assertSee($booking->booking_code);
    }

    public function test_non_customer_cannot_create_booking(): void
    {
        $staff = User::factory()->create([
            'role' => 'support',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->actingAs($staff)
            ->post(route('customer.bookings.store', $this->caregiver), [
                'start_time' => $this->day.'T09:00',
                'end_time' => $this->day.'T11:00',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_code_is_unique(): void
    {
        $codes = collect(range(1, 50))->map(fn () => Booking::generateCode());

        $this->assertEquals(50, $codes->unique()->count());
        $this->assertMatchesRegularExpression('/^BB-\d{8}-[A-Z0-9]{5}$/', $codes->first());
    }

    public function test_booking_progress_steps_reflect_status(): void
    {
        $booking = new Booking(['status' => Booking::STATUS_REQUESTED]);
        $this->assertSame(
            ['current', 'pending', 'pending', 'pending', 'pending'],
            array_column($booking->progressSteps(), 'state')
        );

        $booking->status = Booking::STATUS_ACCEPTED;
        $this->assertSame(
            ['done', 'current', 'pending', 'pending', 'pending'],
            array_column($booking->progressSteps(), 'state')
        );

        $booking->status = Booking::STATUS_COMPLETED;
        $this->assertSame(
            ['done', 'done', 'done', 'done', 'current'],
            array_column($booking->progressSteps(), 'state')
        );

        // Status di luar langkah utama tidak mencapai langkah manapun
        $booking->status = Booking::STATUS_REJECTED;
        $this->assertSame(
            ['pending', 'pending', 'pending', 'pending', 'pending'],
            array_column($booking->progressSteps(), 'state')
        );
    }

    public function test_customer_can_view_invoice_of_own_booking(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 12:00',
            ]);

        $this->actingAs($this->customerUser)
            ->get(route('customer.bookings.invoice', $booking))
            ->assertOk()
            ->assertSee($booking->invoiceNumber())
            // 3 jam x Rp 50.000
            ->assertSee('150.000')
            ->assertSee('Belum ada pembayaran tercatat');
    }

    public function test_caregiver_can_view_invoice_of_own_booking(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);

        $this->actingAs($this->caregiverUser)
            ->get(route('caregiver.bookings.invoice', $booking))
            ->assertOk()
            ->assertSee($booking->invoiceNumber());
    }

    public function test_customer_cannot_view_invoice_of_other_booking(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);

        $other = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create(['user_id' => $other->id]);

        $this->actingAs($other)
            ->get(route('customer.bookings.invoice', $booking))
            ->assertForbidden();
    }

    public function test_invoice_number_is_derived_from_booking_code(): void
    {
        $booking = new Booking(['status' => Booking::STATUS_REQUESTED]);
        $booking->booking_code = 'BB-20260904-7BF19';

        $this->assertSame('INV-20260904-7BF19', $booking->invoiceNumber());
    }

    public function test_invoice_shows_paid_status_when_payment_exists(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);

        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => Payment::generateCode(),
            'amount' => 100000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($this->customerUser)
            ->get(route('customer.bookings.invoice', $booking))
            ->assertOk()
            ->assertSee('Lunas');
    }

    public function test_caregiver_can_cancel_accepted_booking(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        app(BookingService::class)->accept($booking, $this->caregiverUser);

        $this->actingAs($this->caregiverUser)
            ->patch(route('caregiver.bookings.cancel', $booking), ['reason' => 'Keadaan darurat'])
            ->assertSessionHas('status');

        $booking = $booking->fresh();
        $this->assertEquals(Booking::STATUS_CANCELLED, $booking->status);
        $this->assertNotNull($booking->cancelled_at);
        $this->assertEquals('Keadaan darurat', $booking->cancellation_reason);
        // Jadwal dilepas kembali
        $this->assertEquals(Schedule::STATUS_AVAILABLE, $this->caregiver->schedules()->first()->status);
        // Riwayat tercatat dengan aktor caregiver
        $history = $booking->statusHistories()->where('to_status', Booking::STATUS_CANCELLED)->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->caregiverUser->id, $history->changed_by);
        $this->assertEquals(Booking::STATUS_ACCEPTED, $history->from_status);
    }

    public function test_customer_cancel_is_idempotent(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);

        $this->actingAs($this->customerUser)
            ->patch(route('customer.bookings.cancel', $booking))
            ->assertRedirect();

        // Pembatalan kedua ditolak dan tidak menambah riwayat/refund lagi
        $this->actingAs($this->customerUser)
            ->patch(route('customer.bookings.cancel', $booking))
            ->assertSessionHasErrors('booking');

        $this->assertEquals(1, $booking->statusHistories()
            ->where('to_status', Booking::STATUS_CANCELLED)
            ->count());
    }

    public function test_booking_request_declined_while_slot_lock_is_held(): void
    {
        $this->makeSchedule();
        $lock = Cache::lock('booking:caregiver:'.$this->caregiver->id, 15);
        $lock->get();

        [$booking, $error] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);

        $lock->release();

        $this->assertNull($booking);
        $this->assertStringContainsString('diproses', $error);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_replacement_can_only_be_assigned_once(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        app(BookingService::class)->accept($booking, $this->caregiverUser);
        app(BookingService::class)->cancelByCaregiver($booking, $this->caregiverUser, 'Darurat');

        $otherUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);
        $other = Caregiver::create([
            'user_id' => $otherUser->id,
            'skills' => 'Perawatan anak',
            'hourly_rate' => 60000,
            'verification_status' => 'verified',
        ]);
        Schedule::create([
            'caregiver_id' => $other->id,
            'start_time' => $this->day.' 08:00:00',
            'end_time' => $this->day.' 12:00:00',
            'status' => Schedule::STATUS_AVAILABLE,
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'approved_at' => now()]);

        // Penugasan pertama sukses
        $this->actingAs($admin)
            ->patch(route('admin.bookings.replacement.assign', $booking), ['caregiver_id' => $other->id])
            ->assertRedirect(route('admin.bookings.replacement.index'))
            ->assertSessionHas('status');

        // Kandidat kedua juga tersedia, tapi booking sudah ditugaskan
        $thirdUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);
        $third = Caregiver::create([
            'user_id' => $thirdUser->id,
            'hourly_rate' => 45000,
            'verification_status' => 'verified',
        ]);
        Schedule::create([
            'caregiver_id' => $third->id,
            'start_time' => $this->day.' 08:00:00',
            'end_time' => $this->day.' 12:00:00',
            'status' => Schedule::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.bookings.replacement.assign', $booking), ['caregiver_id' => $third->id])
            ->assertSessionHasErrors('caregiver_id');

        // Booking tetap pada caregiver pertama, jadwal ketiga tidak dibooking
        $this->assertEquals($other->id, $booking->fresh()->caregiver_id);
        $this->assertTrue($third->schedules()->where('status', Schedule::STATUS_BOOKED)->doesntExist());
    }

    public function test_caregiver_cannot_cancel_requested_booking(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);

        $this->actingAs($this->caregiverUser)
            ->patch(route('caregiver.bookings.cancel', $booking))
            ->assertSessionHasErrors('booking');

        $this->assertEquals(Booking::STATUS_REQUESTED, $booking->fresh()->status);
        // Untuk booking requested, caregiver menggunakan tombol Tolak
    }

    public function test_customer_cannot_cancel_confirmed_booking(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        app(BookingService::class)->accept($booking, $this->caregiverUser);
        $booking->update(['status' => Booking::STATUS_CONFIRMED]);

        $this->actingAs($this->customerUser)
            ->patch(route('customer.bookings.cancel', $booking))
            ->assertSessionHasErrors('booking');

        $this->assertEquals(Booking::STATUS_CONFIRMED, $booking->fresh()->status);
    }

    public function test_caregiver_cannot_cancel_other_caregiver_booking(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        app(BookingService::class)->accept($booking, $this->caregiverUser);

        $otherCaregiverUser = User::factory()->create([
            'role' => 'caregiver',
            'status' => 'active',
            'approved_at' => now(),
        ]);
        $otherCaregiver = Caregiver::create([
            'user_id' => $otherCaregiverUser->id,
            'hourly_rate' => 45000,
            'verification_status' => 'verified',
        ]);

        $this->actingAs($otherCaregiverUser)
            ->patch(route('caregiver.bookings.cancel', $booking))
            ->assertForbidden();

        $this->assertEquals(Booking::STATUS_ACCEPTED, $booking->fresh()->status);
    }

    public function test_customer_cancel_far_ahead_gets_full_refund(): void
    {
        // Jadwal 3 hari lagi: pembatalan > 48 jam -> refund 100%
        $this->makeSchedule('08:00', '16:00', Schedule::STATUS_AVAILABLE, now()->addDays(3)->format('Y-m-d'));
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => now()->addDays(3)->format('Y-m-d').' 09:00',
                'end_time' => now()->addDays(3)->format('Y-m-d').' 11:00',
            ]);

        $this->actingAs($this->customerUser)
            ->patch(route('customer.bookings.cancel', $booking))
            ->assertRedirect();

        $booking = $booking->fresh();
        $this->assertEquals(Booking::STATUS_CANCELLED, $booking->status);
        $this->assertEquals(100, $booking->refund_percent);
        $this->assertEquals(100000.0, (float) $booking->refund_amount);
        $this->assertEquals(0.0, (float) $booking->cancellation_fee);
    }

    public function test_customer_cancel_near_schedule_gets_partial_refund(): void
    {
        // Jadwal ~25 jam lagi: pembatalan antara 24-48 jam -> refund 50%
        $start = now()->addHours(25);
        Schedule::create([
            'caregiver_id' => $this->caregiver->id,
            'start_time' => $start->copy()->subHours(3),
            'end_time' => $start->copy()->addHours(4),
            'status' => Schedule::STATUS_AVAILABLE,
        ]);
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $start->format('Y-m-d H:i'),
                'end_time' => $start->copy()->addHours(2)->format('Y-m-d H:i'),
            ]);

        $this->actingAs($this->customerUser)
            ->patch(route('customer.bookings.cancel', $booking))
            ->assertRedirect();

        $booking = $booking->fresh();
        $this->assertEquals(50, $booking->refund_percent);
        $this->assertEquals(50000.0, (float) $booking->refund_amount);
        $this->assertEquals(50000.0, (float) $booking->cancellation_fee);
    }

    public function test_customer_cancel_very_late_gets_no_refund(): void
    {
        // Jadwal ~10 jam lagi: pembatalan < 24 jam -> tanpa refund
        $start = now()->addHours(10);
        Schedule::create([
            'caregiver_id' => $this->caregiver->id,
            'start_time' => $start->copy()->subHours(3),
            'end_time' => $start->copy()->addHours(4),
            'status' => Schedule::STATUS_AVAILABLE,
        ]);
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $start->format('Y-m-d H:i'),
                'end_time' => $start->copy()->addHours(2)->format('Y-m-d H:i'),
            ]);

        $this->actingAs($this->customerUser)
            ->patch(route('customer.bookings.cancel', $booking))
            ->assertRedirect();

        $booking = $booking->fresh();
        $this->assertEquals(0, $booking->refund_percent);
        $this->assertEquals(0.0, (float) $booking->refund_amount);
        $this->assertEquals(100000.0, (float) $booking->cancellation_fee);
    }

    public function test_caregiver_cancel_gives_full_refund(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        app(BookingService::class)->accept($booking, $this->caregiverUser);

        $this->actingAs($this->caregiverUser)
            ->patch(route('caregiver.bookings.cancel', $booking), ['reason' => 'Darurat'])
            ->assertSessionHas('status');

        $booking = $booking->fresh();
        $this->assertEquals(100, $booking->refund_percent);
        $this->assertEquals(100000.0, (float) $booking->refund_amount);
        $this->assertEquals(0.0, (float) $booking->cancellation_fee);
    }

    public function test_cancelled_booking_detail_shows_refund_info(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        $this->actingAs($this->customerUser)
            ->patch(route('customer.bookings.cancel', $booking))
            ->assertRedirect();

        $this->actingAs($this->customerUser)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Rincian Refund');
    }

    public function test_cancelled_booking_invoice_shows_refund_lines(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        // Pembatalan dekat jadwal -> ada biaya pembatalan dan refund
        $start = now()->addHours(25);
        $booking->update(['start_time' => $start, 'end_time' => $start->copy()->addHours(2)]);
        app(BookingService::class)->accept($booking, $this->caregiverUser);
        $this->actingAs($this->customerUser)
            ->patch(route('customer.bookings.cancel', $booking))
            ->assertRedirect();

        $this->actingAs($this->customerUser)
            ->get(route('customer.bookings.invoice', $booking))
            ->assertOk()
            ->assertSee('Biaya pembatalan')
            ->assertSee('Refund');
    }

    public function test_caregiver_cancel_marks_booking_needs_replacement(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        app(BookingService::class)->accept($booking, $this->caregiverUser);

        $this->actingAs($this->caregiverUser)
            ->patch(route('caregiver.bookings.cancel', $booking), ['reason' => 'Darurat'])
            ->assertSessionHas('status');

        $booking = $booking->fresh();
        $this->assertTrue($booking->needs_replacement);
        $this->assertNotNull($booking->replacement_offered_at);
    }

    public function test_customer_cancel_does_not_require_replacement(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        $this->actingAs($this->customerUser)
            ->patch(route('customer.bookings.cancel', $booking))
            ->assertRedirect();

        $this->assertFalse($booking->fresh()->needs_replacement);
    }

    public function test_customer_detail_shows_replacement_notice(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        app(BookingService::class)->accept($booking, $this->caregiverUser);
        app(BookingService::class)->cancelByCaregiver($booking, $this->caregiverUser, 'Darurat');

        $this->actingAs($this->customerUser)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Caregiver Pengganti');
    }

    public function test_admin_sees_waiting_replacement_list(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        app(BookingService::class)->accept($booking, $this->caregiverUser);
        app(BookingService::class)->cancelByCaregiver($booking, $this->caregiverUser, 'Darurat');

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'approved_at' => now()]);

        $this->actingAs($admin)
            ->get(route('admin.bookings.replacement.index'))
            ->assertOk()
            ->assertSee($booking->booking_code);
    }

    public function test_admin_replacement_show_lists_available_candidates(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        app(BookingService::class)->accept($booking, $this->caregiverUser);
        app(BookingService::class)->cancelByCaregiver($booking, $this->caregiverUser, 'Darurat');

        // Caregiver kandidat terverifikasi dengan jadwal yang mencakup rentang booking
        $otherUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);
        $other = Caregiver::create([
            'user_id' => $otherUser->id,
            'skills' => 'Perawatan anak',
            'service_area' => 'Jakarta Utara',
            'hourly_rate' => 60000,
            'rating' => 4.9,
            'verification_status' => 'verified',
        ]);
        Schedule::create([
            'caregiver_id' => $other->id,
            'start_time' => $this->day.' 08:00:00',
            'end_time' => $this->day.' 12:00:00',
            'status' => Schedule::STATUS_AVAILABLE,
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'approved_at' => now()]);

        $this->actingAs($admin)
            ->get(route('admin.bookings.replacement.show', $booking))
            ->assertOk()
            ->assertSee($other->user->name)
            ->assertDontSee($this->caregiver->user->name.' Rp');
    }

    public function test_admin_can_assign_replacement_caregiver(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        app(BookingService::class)->accept($booking, $this->caregiverUser);
        app(BookingService::class)->cancelByCaregiver($booking, $this->caregiverUser, 'Darurat');

        $otherUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);
        $other = Caregiver::create([
            'user_id' => $otherUser->id,
            'skills' => 'Perawatan anak',
            'service_area' => 'Jakarta Utara',
            'hourly_rate' => 60000,
            'rating' => 4.9,
            'verification_status' => 'verified',
        ]);
        Schedule::create([
            'caregiver_id' => $other->id,
            'start_time' => $this->day.' 08:00:00',
            'end_time' => $this->day.' 12:00:00',
            'status' => Schedule::STATUS_AVAILABLE,
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'approved_at' => now()]);

        $this->actingAs($admin)
            ->patch(route('admin.bookings.replacement.assign', $booking), ['caregiver_id' => $other->id])
            ->assertRedirect(route('admin.bookings.replacement.index'))
            ->assertSessionHas('status');

        $booking = $booking->fresh();
        $this->assertEquals($other->id, $booking->caregiver_id);
        $this->assertEquals(Booking::STATUS_REQUESTED, $booking->status);
        $this->assertFalse($booking->needs_replacement);
        $this->assertEquals(0, $booking->refund_percent);

        // Jadwal caregiver baru dibooking, riwayat tercatat
        $this->assertTrue($other->schedules()->where('status', Schedule::STATUS_BOOKED)->exists());
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'to_status' => Booking::STATUS_REQUESTED,
        ]);

        // Booking kembali terlihat di daftar caregiver baru
        $this->actingAs($otherUser)
            ->get(route('caregiver.bookings.index'))
            ->assertOk()
            ->assertSee($booking->booking_code);
    }

    public function test_admin_cannot_assign_same_caregiver(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        app(BookingService::class)->accept($booking, $this->caregiverUser);
        app(BookingService::class)->cancelByCaregiver($booking, $this->caregiverUser, 'Darurat');

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'approved_at' => now()]);

        $this->actingAs($admin)
            ->patch(route('admin.bookings.replacement.assign', $booking), ['caregiver_id' => $this->caregiver->id])
            ->assertSessionHasErrors('caregiver_id');
    }

    public function test_admin_cannot_assign_unavailable_replacement(): void
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)
            ->createRequest($this->caregiver, $this->customerUser, [
                'start_time' => $this->day.' 09:00',
                'end_time' => $this->day.' 11:00',
            ]);
        app(BookingService::class)->accept($booking, $this->caregiverUser);
        app(BookingService::class)->cancelByCaregiver($booking, $this->caregiverUser, 'Darurat');

        // Caregiver tanpa jadwal yang mencakup rentang booking
        $otherUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);
        $other = Caregiver::create([
            'user_id' => $otherUser->id,
            'skills' => 'Perawatan anak',
            'hourly_rate' => 60000,
            'verification_status' => 'verified',
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'approved_at' => now()]);

        $this->actingAs($admin)
            ->patch(route('admin.bookings.replacement.assign', $booking), ['caregiver_id' => $other->id])
            ->assertSessionHasErrors('caregiver_id');
    }

    public function test_replacement_routes_require_admin_role(): void
    {
        $this->actingAs($this->customerUser)
            ->get(route('admin.bookings.replacement.index'))
            ->assertForbidden();
    }
}
