<?php

namespace Tests\Feature;

use App\Models\Caregiver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaregiverSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeCaregiverUser(string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@test.local',
            'password' => 'password',
            'role' => 'caregiver',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function makeCaregiver(array $attributes, string $verificationStatus = 'verified'): Caregiver
    {
        return Caregiver::create(array_merge([
            'user_id' => $this->makeCaregiverUser($attributes['name'] ?? 'Test User')->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'rating' => 4.5,
            'verification_status' => $verificationStatus,
        ], $attributes));
    }

    public function test_index_returns_ok_and_shows_verified_caregiver(): void
    {
        $caregiver = $this->makeCaregiver(['name' => 'Sari Wulandari']);

        $response = $this->get(route('caregivers.index'));

        $response->assertOk();
        $response->assertSee($caregiver->user->name);
    }

    public function test_index_hides_unverified_caregivers(): void
    {
        $verified = $this->makeCaregiver(['name' => 'Sari Verified'], 'verified');
        $pending = $this->makeCaregiver(['name' => 'Budi Pending'], 'pending');
        $rejected = $this->makeCaregiver(['name' => 'Citra Rejected'], 'rejected');

        $response = $this->get(route('caregivers.index'));

        $response->assertOk();
        $response->assertSee($verified->user->name);
        $response->assertDontSee($pending->user->name);
        $response->assertDontSee($rejected->user->name);
    }

    public function test_index_hides_caregivers_with_inactive_user(): void
    {
        $user = $this->makeCaregiverUser('Dewi Nonaktif');
        $user->update(['status' => 'suspended']);

        Caregiver::create([
            'user_id' => $user->id,
            'skills' => 'Terapi wicara',
            'service_area' => 'Bandung',
            'hourly_rate' => 30000,
            'rating' => 4.0,
            'verification_status' => 'verified',
        ]);

        $this->get(route('caregivers.index'))->assertDontSee('Dewi Nonaktif');
    }

    public function test_search_by_name_matches(): void
    {
        $sari = $this->makeCaregiver(['name' => 'Sari Wulandari']);
        $this->makeCaregiver(['name' => 'Budi Santoso']);

        $this->get(route('caregivers.index', ['q' => 'Sari']))
            ->assertOk()
            ->assertSee($sari->user->name)
            ->assertDontSee('Budi Santoso');
    }

    public function test_search_by_skill_matches(): void
    {
        $sari = $this->makeCaregiver([
            'name' => 'Sari Wulandari',
            'skills' => 'Perawatan lansia, perawatan luka',
        ]);
        $budi = $this->makeCaregiver(['name' => 'Budi Santoso', 'skills' => 'Terapi wicara']);

        $response = $this->get(route('caregivers.index', ['q' => 'perawatan luka']));

        $response->assertOk();
        $response->assertSee($sari->user->name);
        $response->assertDontSee($budi->user->name);
    }

    public function test_area_filter_matches_partial_and_case_insensitive(): void
    {
        $sari = $this->makeCaregiver(['name' => 'Sari Wulandari', 'service_area' => 'Jakarta Selatan, Jakarta Pusat']);
        $this->makeCaregiver(['name' => 'Budi Santoso', 'service_area' => 'Bandung']);

        $this->get(route('caregivers.index', ['area' => 'jakarta selatan']))
            ->assertOk()
            ->assertSee($sari->user->name)
            ->assertDontSee('Budi Santoso');
    }

    public function test_rate_filter_min_and_max(): void
    {
        $cheap = $this->makeCaregiver(['name' => 'Budi Murah', 'hourly_rate' => 30000]);
        $this->makeCaregiver(['name' => 'Sari Mahal', 'hourly_rate' => 80000]);

        $this->get(route('caregivers.index', ['max_rate' => 50000]))
            ->assertOk()
            ->assertSee($cheap->user->name)
            ->assertDontSee('Sari Mahal');

        $this->get(route('caregivers.index', ['min_rate' => 60000]))
            ->assertOk()
            ->assertDontSee('Budi Murah')
            ->assertSee('Sari Mahal');
    }

    public function test_sort_by_rate_ascending(): void
    {
        $this->makeCaregiver(['name' => 'Sari Mahal', 'hourly_rate' => 80000]);
        $this->makeCaregiver(['name' => 'Budi Murah', 'hourly_rate' => 30000]);

        $response = $this->get(route('caregivers.index', ['sort' => 'rate_asc']));

        $response->assertOk();
        $this->assertSame(
            ['Budi Murah', 'Sari Mahal'],
            $response->viewData('caregivers')->getCollection()->pluck('user.name')->all()
        );
    }

    public function test_filters_are_preserved_across_pagination(): void
    {
        // Buat 13 caregiver agar halaman kedua ada (paginate 12)
        for ($i = 1; $i <= 13; $i++) {
            $this->makeCaregiver([
                'name' => "Caregiver {$i}",
                'service_area' => 'Depok',
                'hourly_rate' => 40000,
            ]);
        }

        $response = $this->get(route('caregivers.index', ['area' => 'Depok', 'page' => 2]));

        $response->assertOk();
        $response->viewData('caregivers')->getCollection()->each(
            fn (Caregiver $c) => $this->assertStringContainsString('Depok', $c->service_area)
        );
    }

    public function test_distance_sort_orders_caregivers_nearest_first(): void
    {
        // Titik acuan: Monas (Jakarta Pusat) ~ -6.1754, 106.8272
        $nearest = $this->makeCaregiver(['name' => 'Sari Dekat', 'latitude' => -6.1850, 'longitude' => 106.8300]);
        $mid = $this->makeCaregiver(['name' => 'Rian Sedang', 'latitude' => -6.2600, 'longitude' => 106.8500]);
        $farthest = $this->makeCaregiver(['name' => 'Budi Jauh', 'latitude' => -6.4000, 'longitude' => 106.9500]);

        $response = $this->get(route('caregivers.index', [
            'lat' => -6.1754,
            'lng' => 106.8272,
            'sort' => 'distance',
        ]));

        $response->assertOk();
        $this->assertSame(
            ['Sari Dekat', 'Rian Sedang', 'Budi Jauh'],
            $response->viewData('caregivers')->getCollection()->pluck('user.name')->all()
        );

        // Jarak dilampirkan ke tiap model
        $nearestKm = $response->viewData('caregivers')->first()->distance_km;
        $this->assertGreaterThan(0, $nearestKm);
        $this->assertLessThan(2, $nearestKm);
    }

    public function test_distance_sort_ignores_caregivers_without_coordinates(): void
    {
        $near = $this->makeCaregiver(['name' => 'Ada Koordinat', 'latitude' => -6.1850, 'longitude' => 106.8300]);
        // Tanpa koordinat: tetap ikut hasil, jarak null
        $this->makeCaregiver(['name' => 'Tanpa Koordinat']);

        $response = $this->get(route('caregivers.index', [
            'lat' => -6.1754,
            'lng' => 106.8272,
            'sort' => 'distance',
        ]));

        $response->assertOk();
        $collection = $response->viewData('caregivers')->getCollection();
        $this->assertSame(['Ada Koordinat', 'Tanpa Koordinat'],
            $collection->pluck('user.name')->all());
        $this->assertNotNull($collection->first()->distance_km);
        $this->assertNull($collection->last()->distance_km);
    }

    public function test_coordinates_attach_distance_even_with_default_sort(): void
    {
        $this->makeCaregiver(['name' => 'Sari Dekat', 'latitude' => -6.1850, 'longitude' => 106.8300]);

        $response = $this->get(route('caregivers.index', ['lat' => -6.1754, 'lng' => 106.8272]));

        $response->assertOk();
        $this->assertNotNull($response->viewData('caregivers')->first()->distance_km);
    }

    public function test_invalid_coordinates_are_ignored(): void
    {
        $this->makeCaregiver(['name' => 'Sari Wulandari']);

        $this->get(route('caregivers.index', ['lat' => 'abc', 'lng' => 999]))
            ->assertOk()
            ->assertSee('Sari Wulandari');

        $this->get(route('caregivers.index', ['lat' => 200, 'lng' => 106.8]))
            ->assertOk()
            ->assertSee('Sari Wulandari');
    }

    public function test_distance_sort_respects_filters(): void
    {
        $this->makeCaregiver(['name' => 'Sari Dekat', 'latitude' => -6.1850, 'longitude' => 106.8300, 'hourly_rate' => 80000]);
        $this->makeCaregiver(['name' => 'Budi Dekat Murah', 'latitude' => -6.1860, 'longitude' => 106.8310, 'hourly_rate' => 30000]);

        $response = $this->get(route('caregivers.index', [
            'lat' => -6.1754,
            'lng' => 106.8272,
            'sort' => 'distance',
            'max_rate' => 50000,
        ]));

        $response->assertOk();
        $this->assertSame(
            ['Budi Dekat Murah'],
            $response->viewData('caregivers')->getCollection()->pluck('user.name')->all()
        );
    }

    public function test_pagination_works_with_distance_sort(): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->makeCaregiver([
                'name' => sprintf('Caregiver %02d', $i),
                // Jarak meningkat sesuai nomor agar urutan deterministik
                'latitude' => -6.1850 - $i * 0.02,
                'longitude' => 106.8300,
            ]);
        }

        $response = $this->get(route('caregivers.index', [
            'lat' => -6.1754,
            'lng' => 106.8272,
            'sort' => 'distance',
            'page' => 2,
        ]));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('caregivers'));
        $this->assertSame('Caregiver 13', $response->viewData('caregivers')->first()->user->name);
    }
}
