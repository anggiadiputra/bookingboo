<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaregiverPublicProfileTest extends TestCase
{
    use RefreshDatabase;

    private function makeCaregiver(string $verificationStatus = 'verified'): Caregiver
    {
        $user = User::factory()->create(['role' => 'caregiver']);

        return Caregiver::create([
            'user_id' => $user->id,
            'skills' => 'Perawatan lansia, pendampingan kontrol',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'rating' => 4.8,
            'verification_status' => $verificationStatus,
            'bio' => 'Caregiver berpengalaman.',
        ]);
    }

    public function test_public_profile_shows_caregiver_details(): void
    {
        $caregiver = $this->makeCaregiver();

        $response = $this->get("/caregivers/{$caregiver->user->public_id}");

        $response->assertStatus(200);
        $response->assertSee($caregiver->user->name);
        $response->assertSee('Rp 50.000');
        $response->assertSee('Perawatan lansia');
        $response->assertSee('Terverifikasi');
    }

    public function test_public_profile_shows_only_public_reviews(): void
    {
        $caregiver = $this->makeCaregiver();
        $customerUser = User::factory()->create(['role' => 'customer']);
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'address' => 'Jl. Test',
            'emergency_contact' => 'Siti',
            'emergency_contact_phone' => '081234567890',
        ]);
        $booking = Booking::create([
            'booking_code' => 'BB-TEST-1',
            'customer_id' => $customer->id,
            'caregiver_id' => $caregiver->id,
            'start_time' => now()->subDay(),
            'end_time' => now()->subDay()->addHours(2),
            'status' => 'completed',
            'total_amount' => 100000,
        ]);

        Review::create([
            'booking_id' => $booking->id,
            'reviewer_id' => $customerUser->id,
            'reviewee_id' => $caregiver->user_id,
            'rating' => 5,
            'comment' => 'Review publik yang terlihat.',
            'visibility' => 'public',
        ]);
        Review::create([
            'booking_id' => $booking->id,
            'reviewer_id' => $caregiver->user_id,
            'reviewee_id' => $customerUser->id,
            'rating' => 4,
            'comment' => 'Review privat yang tidak boleh tampil.',
            'visibility' => 'private',
        ]);

        $response = $this->get("/caregivers/{$caregiver->user->public_id}");

        $response->assertStatus(200);
        $response->assertSee('Review publik yang terlihat.');
        $response->assertDontSee('Review privat yang tidak boleh tampil.');
    }

    public function test_unverified_caregiver_returns_404(): void
    {
        $caregiver = $this->makeCaregiver('pending');

        $this->get("/caregivers/{$caregiver->user->public_id}")->assertStatus(404);
    }

    public function test_internal_id_url_returns_404(): void
    {
        $caregiver = $this->makeCaregiver();

        $this->get("/caregivers/{$caregiver->id}")->assertStatus(404);
    }
}
