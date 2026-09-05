<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur check-in / check-out layanan oleh caregiver.
 * Guard: party, status, urutan, waktu mulai; catat riwayat status.
 */
class CheckinCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeConfirmedBooking(array $overrides = []): Booking
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
            'status' => Booking::STATUS_CONFIRMED,
            'total_amount' => 150000,
        ], $overrides));
    }

    private function caregiverOf(Booking $booking): User
    {
        return User::find($booking->caregiver->user_id);
    }

    public function test_caregiver_can_check_in_when_service_starts(): void
    {
        $booking = $this->makeConfirmedBooking(['start_time' => now()->subMinutes(30), 'end_time' => now()->addMinutes(90)]);
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.check-in', $booking))
            ->assertRedirect()
            ->assertSessionHas('status');

        $booking->refresh();
        $this->assertSame(Booking::STATUS_IN_PROGRESS, $booking->status);
        $this->assertNotNull($booking->check_in_at);
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'from_status' => 'confirmed',
            'to_status' => 'in_progress',
            'note' => 'Check-in oleh caregiver.',
        ]);
    }

    public function test_check_in_rejected_before_start_time(): void
    {
        $booking = $this->makeConfirmedBooking(); // mulai H+3
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.check-in', $booking))
            ->assertRedirect()
            ->assertSessionHasErrors(['booking']);

        $booking->refresh();
        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->status);
        $this->assertNull($booking->check_in_at);
    }

    public function test_check_in_rejected_from_accepted_status(): void
    {
        $booking = $this->makeConfirmedBooking([
            'status' => Booking::STATUS_ACCEPTED,
            'start_time' => now()->subMinutes(30),
            'end_time' => now()->addMinutes(90),
        ]);
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.check-in', $booking))
            ->assertRedirect()
            ->assertSessionHasErrors(['booking']);

        $this->assertSame(Booking::STATUS_ACCEPTED, $booking->refresh()->status);
    }

    public function test_check_in_is_idempotent_when_already_done(): void
    {
        $booking = $this->makeConfirmedBooking([
            'status' => Booking::STATUS_IN_PROGRESS,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'check_in_at' => now()->subMinutes(30),
        ]);
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.check-in', $booking))
            ->assertRedirect()
            ->assertSessionHasErrors(['booking']);

        $this->assertNull($booking->refresh()->check_out_at);
    }

    public function test_caregiver_can_check_out_after_check_in(): void
    {
        $booking = $this->makeConfirmedBooking([
            'status' => Booking::STATUS_IN_PROGRESS,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'check_in_at' => now()->subMinutes(30),
        ]);
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.check-out', $booking))
            ->assertRedirect()
            ->assertSessionHas('status');

        $booking->refresh();
        $this->assertSame(Booking::STATUS_COMPLETED, $booking->status);
        $this->assertNotNull($booking->check_out_at);
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'from_status' => 'in_progress',
            'to_status' => 'completed',
            'note' => 'Check-out oleh caregiver.',
        ]);
    }

    public function test_check_out_rejected_without_check_in(): void
    {
        $booking = $this->makeConfirmedBooking(); // belum check-in
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.check-out', $booking))
            ->assertRedirect()
            ->assertSessionHasErrors(['booking']);

        $booking->refresh();
        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->status);
        $this->assertNull($booking->check_out_at);
    }

    public function test_check_out_rejected_when_already_completed(): void
    {
        $booking = $this->makeConfirmedBooking([
            'status' => Booking::STATUS_COMPLETED,
            'start_time' => now()->subHours(3),
            'end_time' => now()->subHour(),
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subMinutes(30),
        ]);
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.check-out', $booking))
            ->assertRedirect()
            ->assertSessionHasErrors(['booking']);

        // Waktu checkout tidak berubah, tidak ada riwayat checkout kedua
        $this->assertNotNull($booking->refresh()->check_out_at);
        $this->assertDatabaseCount('booking_status_histories', 0);
    }

    public function test_customer_cannot_check_in(): void
    {
        $booking = $this->makeConfirmedBooking([
            'start_time' => now()->subMinutes(30),
            'end_time' => now()->addMinutes(90),
        ]);
        $user = User::find($booking->customer->user_id);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.check-in', $booking))
            ->assertForbidden();
    }

    public function test_other_caregiver_cannot_check_in_someone_elses_booking(): void
    {
        $booking = $this->makeConfirmedBooking([
            'start_time' => now()->subMinutes(30),
            'end_time' => now()->addMinutes(90),
        ]);

        $otherUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);
        Caregiver::create([
            'user_id' => $otherUser->id,
            'skills' => 'Terapis wicara',
            'service_area' => 'Bekasi',
            'hourly_rate' => 60000,
            'verification_status' => 'verified',
        ]);

        $this->actingAs($otherUser)
            ->post(route('caregiver.bookings.check-in', $booking))
            ->assertForbidden();
    }

    public function test_detail_shows_checkin_and_checkout_info(): void
    {
        $booking = $this->makeConfirmedBooking([
            'status' => Booking::STATUS_COMPLETED,
            'start_time' => now()->subHours(3),
            'end_time' => now()->subHour(),
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subMinutes(30),
        ]);
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->get(route('caregiver.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Waktu Layanan Aktual')
            ->assertSee('Check-in:');
    }

    public function test_detail_shows_checkin_button_after_start_time(): void
    {
        $booking = $this->makeConfirmedBooking([
            'start_time' => now()->subMinutes(30),
            'end_time' => now()->addMinutes(90),
        ]);
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->get(route('caregiver.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Absensi Layanan')
            ->assertSee('Check-in');
    }

    public function test_detail_hides_checkin_button_before_start_time(): void
    {
        $booking = $this->makeConfirmedBooking(); // mulai H+3
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->get(route('caregiver.bookings.show', $booking))
            ->assertOk()
            ->assertDontSee('Absensi Layanan');
    }
}
