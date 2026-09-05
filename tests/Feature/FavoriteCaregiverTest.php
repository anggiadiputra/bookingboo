<?php

namespace Tests\Feature;

use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-28: Daftar caregiver favorit customer.
 * Customer dapat menandai/membatalkan caregiver favorit dari profil publik
 * caregiver, dan melihat daftar favorit di dashboard. Hanya customer yang
 * login & terverifikasi yang dapat toggle; pasangan customer-caregiver unik.
 */
class FavoriteCaregiverTest extends TestCase
{
    use RefreshDatabase;

    private function makeActors(): array
    {
        $customerUser = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $customer = Customer::create(['user_id' => $customerUser->id]);

        $caregiverUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);
        $caregiver = Caregiver::create([
            'user_id' => $caregiverUser->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'rating' => 4.5,
            'verification_status' => 'verified',
        ]);

        return [$customerUser, $customer, $caregiver];
    }

    public function test_customer_can_add_caregiver_to_favorites(): void
    {
        [$customerUser, , $caregiver] = $this->makeActors();

        $this->actingAs($customerUser)
            ->post(route('customer.favorites.toggle', $caregiver))
            ->assertRedirect();

        $this->assertDatabaseHas('customer_favorites', [
            'customer_id' => $customerUser->customer->id,
            'caregiver_id' => $caregiver->id,
        ]);
        $this->assertSame(1, $customerUser->customer->favoriteCaregivers()->count());
    }

    public function test_customer_can_remove_caregiver_from_favorites(): void
    {
        [$customerUser, $customer, $caregiver] = $this->makeActors();
        $customer->favoriteCaregivers()->syncWithoutDetaching([$caregiver->id]);

        $this->actingAs($customerUser)
            ->post(route('customer.favorites.toggle', $caregiver))
            ->assertRedirect();

        $this->assertDatabaseMissing('customer_favorites', [
            'customer_id' => $customer->id,
            'caregiver_id' => $caregiver->id,
        ]);
    }

    public function test_toggling_twice_removes_favorite_again(): void
    {
        [$customerUser, , $caregiver] = $this->makeActors();

        $this->actingAs($customerUser)->post(route('customer.favorites.toggle', $caregiver));
        $this->assertDatabaseCount('customer_favorites', 1);

        $this->actingAs($customerUser)->post(route('customer.favorites.toggle', $caregiver));
        $this->assertDatabaseCount('customer_favorites', 0);
    }

    public function test_repeated_sync_does_not_duplicate_favorite(): void
    {
        [$customerUser, $customer, $caregiver] = $this->makeActors();

        $customer->favoriteCaregivers()->syncWithoutDetaching([$caregiver->id]);
        $customer->favoriteCaregivers()->syncWithoutDetaching([$caregiver->id]);

        $this->assertSame(1, \DB::table('customer_favorites')->count());
    }

    public function test_guest_cannot_toggle_favorite(): void
    {
        [, , $caregiver] = $this->makeActors();

        $this->post(route('customer.favorites.toggle', $caregiver))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('customer_favorites', 0);
    }

    public function test_caregiver_cannot_toggle_favorite(): void
    {
        [, , $caregiver] = $this->makeActors();

        $caregiverUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);

        $this->actingAs($caregiverUser)
            ->post(route('customer.favorites.toggle', $caregiver))
            ->assertForbidden();

        $this->assertDatabaseCount('customer_favorites', 0);
    }

    public function test_staff_cannot_toggle_favorite(): void
    {
        [, , $caregiver] = $this->makeActors();

        $staff = User::factory()->create(['role' => 'support', 'status' => 'active']);

        $this->actingAs($staff)
            ->post(route('customer.favorites.toggle', $caregiver))
            ->assertForbidden();

        $this->assertDatabaseCount('customer_favorites', 0);
    }

    public function test_dashboard_shows_favorited_caregivers(): void
    {
        [$customerUser, $customer, $caregiver] = $this->makeActors();
        $customer->favoriteCaregivers()->syncWithoutDetaching([$caregiver->id]);

        $response = $this->actingAs($customerUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee($caregiver->user->name);
        $response->assertSee('Caregiver Favorit');
    }

    public function test_dashboard_shows_empty_favorites_message(): void
    {
        [$customerUser] = $this->makeActors();

        $response = $this->actingAs($customerUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Belum ada caregiver favorit');
    }

    public function test_public_caregiver_profile_shows_favorite_toggle_for_customer(): void
    {
        [$customerUser, , $caregiver] = $this->makeActors();

        $this->actingAs($customerUser)
            ->get(route('caregivers.show', $caregiver))
            ->assertOk()
            ->assertSee('Tambah ke Favorit');
    }

    public function test_favorite_toggle_shown_only_for_verified_caregiver_profile(): void
    {
        [$customerUser] = $this->makeActors();

        $pendingUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);
        $pending = Caregiver::create([
            'user_id' => $pendingUser->id,
            'skills' => 'Perawatan anak',
            'service_area' => 'Jakarta Pusat',
            'hourly_rate' => 45000,
            'verification_status' => 'pending',
        ]);

        $this->actingAs($customerUser)
            ->get(route('caregivers.show', $pending))
            ->assertNotFound();
    }
}
