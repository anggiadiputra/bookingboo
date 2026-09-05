<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Review publik customer ke caregiver pada booking selesai.
 */
class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpActors();
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

        $this->ids = [
            'customer' => $customerUser->id,
            'caregiver_user' => $caregiverUser->id,
            'caregiver' => $caregiver->id,
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
            'status' => Booking::STATUS_COMPLETED,
            'total_amount' => 150000,
        ], $overrides));
    }

    public function test_customer_can_submit_public_review_on_completed_booking(): void
    {
        $booking = $this->makeBooking();
        $user = User::find($this->ids['customer']);

        $this->actingAs($user)
            ->post(route('customer.bookings.review.store', $booking), [
                'rating' => 5,
                'comment' => 'Sangat perhatian dan profesional.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'reviewer_id' => $this->ids['customer'],
            'reviewee_id' => $this->ids['caregiver_user'],
            'rating' => 5,
            'visibility' => 'public',
        ]);
    }

    public function test_review_updates_caregiver_average_rating(): void
    {
        $booking = $this->makeBooking();
        $user = User::find($this->ids['customer']);

        $this->actingAs($user)
            ->post(route('customer.bookings.review.store', $booking), ['rating' => 4]);

        $this->assertDatabaseHas('caregivers', [
            'id' => $this->ids['caregiver'],
            'rating' => 4.00,
        ]);

        // Review kedua dari customer lain memberi 5 -> rata-rata 4.5
        $customer2 = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create(['user_id' => $customer2->id]);
        $booking2 = $this->makeBooking([
            'booking_code' => Booking::generateCode(),
            'customer_id' => $customer2->customer->id,
            'start_time' => '2026-09-02 09:00:00',
            'end_time' => '2026-09-02 12:00:00',
        ]);

        $this->actingAs($customer2)
            ->post(route('customer.bookings.review.store', $booking2), ['rating' => 5]);

        $this->assertDatabaseHas('caregivers', [
            'id' => $this->ids['caregiver'],
            'rating' => 4.50,
        ]);
    }

    public function test_only_completed_booking_can_be_reviewed(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_ACCEPTED]);
        $user = User::find($this->ids['customer']);

        $this->actingAs($user)
            ->post(route('customer.bookings.review.store', $booking), ['rating' => 5])
            ->assertSessionHasErrors('review');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_is_one_per_booking(): void
    {
        $booking = $this->makeBooking();
        $user = User::find($this->ids['customer']);

        $this->actingAs($user)
            ->post(route('customer.bookings.review.store', $booking), ['rating' => 5]);

        $this->actingAs($user)
            ->post(route('customer.bookings.review.store', $booking), ['rating' => 3])
            ->assertSessionHasErrors('review');

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_rating_is_required_and_bounded(): void
    {
        $booking = $this->makeBooking();
        $user = User::find($this->ids['customer']);

        $this->actingAs($user)
            ->post(route('customer.bookings.review.store', $booking), [])
            ->assertSessionHasErrors('rating');

        $this->actingAs($user)
            ->post(route('customer.bookings.review.store', $booking), ['rating' => 6])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_other_customer_cannot_review_someone_elses_booking(): void
    {
        $booking = $this->makeBooking();
        $other = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create(['user_id' => $other->id]);

        $this->actingAs($other)
            ->post(route('customer.bookings.review.store', $booking), ['rating' => 1])
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_public_profile_shows_reviews_and_rating(): void
    {
        $booking = $this->makeBooking();
        $user = User::find($this->ids['customer']);

        $this->actingAs($user)
            ->post(route('customer.bookings.review.store', $booking), [
                'rating' => 5,
                'comment' => 'Perawatan sangat baik.',
            ]);

        $caregiverUser = User::find($this->ids['caregiver_user']);
        $caregiver = Caregiver::find($this->ids['caregiver']);

        $this->get(route('caregivers.show', $caregiver))
            ->assertOk()
            ->assertSee('Perawatan sangat baik.')
            ->assertSee($user->name);

        $this->assertSame(5.0, (float) $caregiver->fresh()->rating);
    }

    public function test_private_review_not_shown_on_public_profile(): void
    {
        // Review privat (caregiver ke customer) tidak boleh tampil di profil publik caregiver.
        $booking = $this->makeBooking();
        Review::create([
            'booking_id' => $booking->id,
            'reviewer_id' => $this->ids['caregiver_user'],
            'reviewee_id' => $this->ids['customer'],
            'rating' => 1,
            'comment' => 'Catatan privat tentang pasien.',
            'visibility' => Review::VISIBILITY_PRIVATE,
        ]);

        $caregiver = Caregiver::find($this->ids['caregiver']);
        $this->get(route('caregivers.show', $caregiver))
            ->assertOk()
            ->assertDontSee('Catatan privat tentang pasien.');
    }

    public function test_caregiver_booking_detail_shows_customer_review(): void
    {
        $booking = $this->makeBooking();
        $user = User::find($this->ids['customer']);

        $this->actingAs($user)
            ->post(route('customer.bookings.review.store', $booking), [
                'rating' => 4,
                'comment' => 'Cukup baik.',
            ]);

        $caregiverUser = User::find($this->ids['caregiver_user']);
        $this->actingAs($caregiverUser)
            ->get(route('caregiver.bookings.show', $booking))
            ->assertOk();
    }
}
