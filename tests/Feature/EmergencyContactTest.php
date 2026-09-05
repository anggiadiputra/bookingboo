<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-27: Kontak darurat pasien.
 * Customer menyimpan kontak darurat di profil; caregiver yang menangani booking
 * yang sudah dikonfirmasi/berjalan dapat melihatnya untuk keadaan darurat medis.
 * Data tidak boleh bocor sebelum layanan disetujui atau ke pihak lain.
 */
class EmergencyContactTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(array $overrides = []): Booking
    {
        $customerUser = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create([
            'user_id' => $customerUser->id,
            'emergency_contact' => $overrides['emergency_contact'] ?? 'Siti — Kakak Pasien',
            'emergency_contact_phone' => $overrides['emergency_contact_phone'] ?? '081234567890',
        ]);
        $caregiverUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);

        $caregiver = Caregiver::create([
            'user_id' => $caregiverUser->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'verification_status' => 'verified',
        ]);

        return Booking::create(array_merge([
            'booking_code' => Booking::generateCode(),
            'customer_id' => $customerUser->customer->id,
            'caregiver_id' => $caregiver->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(3),
            'status' => Booking::STATUS_CONFIRMED,
            'total_amount' => 150000,
        ], $overrides));
    }

    private function caregiverOf(Booking $booking): User
    {
        return User::find($booking->caregiver->user_id);
    }

    public function test_customer_saves_emergency_contact_in_profile(): void
    {
        $customerUser = User::factory()->create(['role' => 'customer']);
        Customer::create(['user_id' => $customerUser->id]);

        $this->actingAs($customerUser)->patch('/customer/profile', [
            'emergency_contact' => 'Rina — Istri Pasien',
            'emergency_contact_phone' => '081998877665',
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('customers', [
            'user_id' => $customerUser->id,
            'emergency_contact' => 'Rina — Istri Pasien',
            'emergency_contact_phone' => '081998877665',
        ]);
    }

    public function test_caregiver_sees_emergency_contact_on_confirmed_booking(): void
    {
        $booking = $this->makeBooking();
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->get(route('caregiver.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Kontak Darurat Pasien')
            ->assertSee('Siti — Kakak Pasien')
            ->assertSee('081234567890');
    }

    public function test_caregiver_sees_emergency_contact_on_in_progress_booking(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_IN_PROGRESS]);
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->get(route('caregiver.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Kontak Darurat Pasien')
            ->assertSee('081234567890');
    }

    public function test_emergency_contact_hidden_before_service_confirmed(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_REQUESTED]);
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->get(route('caregiver.bookings.show', $booking))
            ->assertOk()
            ->assertDontSee('Kontak Darurat Pasien')
            ->assertDontSee('081234567890');
    }

    public function test_emergency_contact_hidden_after_booking_completed(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_COMPLETED]);
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->get(route('caregiver.bookings.show', $booking))
            ->assertOk()
            ->assertDontSee('Kontak Darurat Pasien')
            ->assertDontSee('081234567890');
    }

    public function test_customer_does_not_see_own_emergency_contact_on_booking_detail(): void
    {
        $booking = $this->makeBooking();
        $customerUser = User::find($booking->customer->user_id);

        $this->actingAs($customerUser)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertDontSee('Kontak Darurat Pasien')
            ->assertDontSee('081234567890');
    }

    public function test_emergency_contact_hidden_when_not_set(): void
    {
        $booking = $this->makeBooking();
        $booking->customer->update(['emergency_contact' => null, 'emergency_contact_phone' => null]);
        $user = $this->caregiverOf($booking);

        $this->actingAs($user)
            ->get(route('caregiver.bookings.show', $booking))
            ->assertOk()
            ->assertDontSee('Kontak Darurat Pasien');
    }
}
