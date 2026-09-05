<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Catatan privat caregiver ke customer pada booking selesai.
 * Reuse tabel reviews (visibility=private), hanya terlihat oleh caregiver penulis.
 */
class CaregiverNoteTest extends TestCase
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

    public function test_caregiver_can_create_private_note_on_completed_booking(): void
    {
        $booking = $this->makeBooking();
        $user = User::find($this->ids['caregiver_user']);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.note.store', $booking), [
                'rating' => 4,
                'note' => 'Keluarga responsif, pasien kooperatif.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'reviewer_id' => $this->ids['caregiver_user'],
            'reviewee_id' => $this->ids['customer'],
            'rating' => 4,
            'visibility' => 'private',
        ]);
    }

    public function test_note_is_one_per_booking(): void
    {
        $booking = $this->makeBooking();
        $user = User::find($this->ids['caregiver_user']);

        $this->actingAs($user)->post(route('caregiver.bookings.note.store', $booking), [
            'rating' => 4,
            'note' => 'Catatan pertama.',
        ]);

        $this->actingAs($user)->post(route('caregiver.bookings.note.store', $booking), [
            'rating' => 2,
            'note' => 'Catatan kedua.',
        ])->assertSessionHasErrors('note');

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_note_only_on_completed_booking(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_ACCEPTED]);
        $user = User::find($this->ids['caregiver_user']);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.note.store', $booking), ['rating' => 5, 'note' => 'x'])
            ->assertSessionHasErrors('note');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_note_validation(): void
    {
        $booking = $this->makeBooking();
        $user = User::find($this->ids['caregiver_user']);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.note.store', $booking), ['note' => 'tanpa rating'])
            ->assertSessionHasErrors('rating');

        $this->actingAs($user)
            ->post(route('caregiver.bookings.note.store', $booking), ['rating' => 4])
            ->assertSessionHasErrors('note');

        $this->actingAs($user)
            ->post(route('caregiver.bookings.note.store', $booking), ['rating' => 4, 'note' => ''])
            ->assertSessionHasErrors('note');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_other_caregiver_cannot_note_someone_elses_booking(): void
    {
        $booking = $this->makeBooking();
        $otherCaregiverUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);
        Caregiver::create([
            'user_id' => $otherCaregiverUser->id,
            'skills' => 'x',
            'service_area' => 'Bandung',
            'hourly_rate' => 40000,
            'verification_status' => 'verified',
        ]);

        $this->actingAs($otherCaregiverUser)
            ->post(route('caregiver.bookings.note.store', $booking), ['rating' => 1, 'note' => 'bukan milikku'])
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_note_not_shown_on_public_profile(): void
    {
        $booking = $this->makeBooking();
        $user = User::find($this->ids['caregiver_user']);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.note.store', $booking), [
                'rating' => 3,
                'note' => 'Kondisi rumah kurang aman untuk pasien.',
            ]);

        $caregiver = Caregiver::find($this->ids['caregiver']);
        $this->get(route('caregivers.show', $caregiver))
            ->assertOk()
            ->assertDontSee('Kondisi rumah kurang aman untuk pasien.');
    }

    public function test_note_visible_only_to_writing_caregiver(): void
    {
        $booking = $this->makeBooking();
        $user = User::find($this->ids['caregiver_user']);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.note.store', $booking), [
                'rating' => 4,
                'note' => 'Rincian pengalaman privat saya.',
            ]);

        // Penulis melihat catatannya sendiri
        $this->actingAs($user)
            ->get(route('caregiver.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Catatan Privat')
            ->assertSee('Rincian pengalaman privat saya.');

        // Customer TIDAK melihat catatan privat caregiver di detail booking-nya
        $customerUser = User::find($this->ids['customer']);
        $this->actingAs($customerUser)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertDontSee('Rincian pengalaman privat saya.');

        // Customer tidak boleh memanggil endpoint catatan
        $this->actingAs($customerUser)
            ->post(route('caregiver.bookings.note.store', $booking), ['rating' => 5, 'note' => 'x'])
            ->assertForbidden();
    }

    public function test_note_does_not_affect_caregiver_rating(): void
    {
        $booking = $this->makeBooking();
        $user = User::find($this->ids['caregiver_user']);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.note.store', $booking), ['rating' => 1, 'note' => 'Rating ini tidak boleh memengaruhi caregiver.']);

        $this->assertDatabaseHas('caregivers', [
            'id' => $this->ids['caregiver'],
            'rating' => 0,
        ]);
    }
}
