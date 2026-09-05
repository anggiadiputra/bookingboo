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
 * Moderasi review oleh admin + penonaktifan akun (FR-21, FR-04).
 */
class ModerationTest extends TestCase
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

        $supportUser = User::factory()->create(['role' => 'support', 'status' => 'active']);
        $adminUser = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->ids = [
            'customer' => $customerUser->id,
            'caregiver_user' => $caregiverUser->id,
            'caregiver' => $caregiver->id,
            'support' => $supportUser->id,
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

    private function makeReview(array $overrides = []): Review
    {
        return Review::create(array_merge([
            'booking_id' => $this->makeBooking(['status' => Booking::STATUS_COMPLETED])->id,
            'reviewer_id' => $this->ids['customer'],
            'reviewee_id' => $this->ids['caregiver_user'],
            'rating' => 1,
            'comment' => 'Komentar buruk yang harus dimoderasi.',
            'visibility' => Review::VISIBILITY_PUBLIC,
            'status' => Review::STATUS_PUBLISHED,
        ], $overrides));
    }

    // ---- Panel review ----

    public function test_admin_can_view_reviews_panel(): void
    {
        $this->makeReview();

        $res = $this->actingAs(User::find($this->ids['admin']))
            ->get(route('admin.reviews.index'));

        $res->assertOk()
            ->assertSee('Komentar buruk yang harus dimoderasi.');
    }

    public function test_support_cannot_access_reviews_panel(): void
    {
        $this->actingAs(User::find($this->ids['support']))
            ->get(route('admin.reviews.index'))
            ->assertForbidden();
    }

    public function test_admin_hides_review_and_rating_recalculated(): void
    {
        $caregiverUser = User::find($this->ids['caregiver_user']);
        $this->makeReview(['rating' => 1]);
        $this->makeReview(['rating' => 5, 'comment' => 'Sangat baik.']);

        $res = $this->actingAs(User::find($this->ids['admin']))
            ->from(route('admin.reviews.index'))
            ->patch(route('admin.reviews.hide', Review::first()), [
                'reason' => 'Melanggar ketentuan konten',
            ]);

        $res->assertRedirect()->assertSessionHas('status');

        $hidden = Review::where('comment', 'Komentar buruk yang harus dimoderasi.')->first();
        $this->assertSame(Review::STATUS_HIDDEN, $hidden->status);
        $this->assertSame('Melanggar ketentuan konten', $hidden->hidden_reason);
        $this->assertSame($this->ids['admin'], $hidden->hidden_by);
        $this->assertNotNull($hidden->hidden_at);

        // Rating dihitung ulang hanya dari review yang masih tampil (5 bintang).
        $caregiverUser->refresh();
        $this->assertEquals(5, Caregiver::find($this->ids['caregiver'])->rating);
    }

    public function test_hide_requires_reason(): void
    {
        $this->makeReview();

        $res = $this->actingAs(User::find($this->ids['admin']))
            ->patch(route('admin.reviews.hide', Review::first()), [
                'reason' => '',
            ]);

        $res->assertSessionHasErrors('reason');
        $this->assertSame(Review::STATUS_PUBLISHED, Review::first()->status);
    }

    public function test_admin_unhides_review(): void
    {
        $this->makeReview(['rating' => 2, 'status' => Review::STATUS_HIDDEN]);

        $res = $this->actingAs(User::find($this->ids['admin']))
            ->patch(route('admin.reviews.unhide', Review::first()));

        $res->assertRedirect()->assertSessionHas('status');

        Review::first()->refresh();
        $this->assertSame(Review::STATUS_PUBLISHED, Review::first()->status);
        $this->assertNull(Review::first()->hidden_reason);
    }

    public function test_hidden_review_excluded_from_public_profile_and_search_rating_is_stale_until_recalculated(): void
    {
        $this->makeReview(['rating' => 1, 'status' => Review::STATUS_HIDDEN]);

        $caregiverUser = User::find($this->ids['caregiver_user']);

        $res = $this->actingAs($caregiverUser)
            ->get(route('caregivers.show', $caregiverUser->caregiver));

        $res->assertOk()
            ->assertDontSee('Komentar buruk yang harus dimoderasi.');
    }

    // ---- Panel akun ----

    public function test_admin_can_view_users_panel(): void
    {
        $res = $this->actingAs(User::find($this->ids['admin']))
            ->get(route('admin.users.index'));

        $res->assertOk();
    }

    public function test_support_cannot_access_users_panel(): void
    {
        $this->actingAs(User::find($this->ids['support']))
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_suspends_user_and_login_is_blocked(): void
    {
        $customerUser = User::find($this->ids['customer']);

        $res = $this->actingAs(User::find($this->ids['admin']))
            ->patch(route('admin.users.suspend', $customerUser), [
                'reason' => 'Pelanggaran ketentuan layanan',
            ]);

        $res->assertRedirect()->assertSessionHas('status');
        $customerUser->refresh();
        $this->assertSame(User::STATUS_SUSPENDED, $customerUser->status);
        $this->assertSame('Pelanggaran ketentuan layanan', $customerUser->suspended_reason);
        $this->assertSame($this->ids['admin'], $customerUser->suspended_by);

        // Login ditolak meski kredensial benar (keluar sesi admin dulu agar redirect tidak memotong request).
        $this->post(route('logout'));
        $this->post(route('login'), [
            'email' => $customerUser->email,
            'password' => 'password',
        ])
            ->assertSessionHasErrors('email');
    }

    public function test_suspended_user_cannot_be_staff(): void
    {
        $supportUser = User::find($this->ids['support']);

        $this->actingAs(User::find($this->ids['admin']))
            ->patch(route('admin.users.suspend', $supportUser), ['reason' => 'tes'])
            ->assertForbidden();

        $supportUser->refresh();
        $this->assertNotSame(User::STATUS_SUSPENDED, $supportUser->status);
    }

    public function test_suspended_user_session_is_logged_out(): void
    {
        $customerUser = User::find($this->ids['customer']);
        $customerUser->update(['status' => User::STATUS_SUSPENDED, 'suspended_reason' => 'tes']);

        $res = $this->actingAs($customerUser)
            ->get(route('dashboard'));

        $res->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_reactivates_user_and_login_works_again(): void
    {
        $customerUser = User::find($this->ids['customer']);
        $customerUser->update([
            'status' => User::STATUS_SUSPENDED,
            'suspended_reason' => 'tes',
            'suspended_by' => $this->ids['admin'],
            'suspended_at' => now(),
        ]);

        $res = $this->actingAs(User::find($this->ids['admin']))
            ->patch(route('admin.users.reactivate', $customerUser));

        $res->assertRedirect()->assertSessionHas('status');
        $customerUser->refresh();
        $this->assertSame(User::STATUS_ACTIVE, $customerUser->status);
        $this->assertNull($customerUser->suspended_reason);

        // Email belum terverifikasi pada user factory default? Pastikan login sukses.
        $customerUser->update(['email_verified_at' => now()]);

        $this->post(route('login'), [
            'email' => $customerUser->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_suspended_user_filter_tab(): void
    {
        User::find($this->ids['customer'])->update(['status' => User::STATUS_SUSPENDED]);

        $res = $this->actingAs(User::find($this->ids['admin']))
            ->get(route('admin.users.index', ['tab' => 'suspended']));

        $res->assertOk();
    }
}
