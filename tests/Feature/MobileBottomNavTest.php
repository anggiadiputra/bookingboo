<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class MobileBottomNavTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_profile_tab_points_to_dashboard(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $html = $this->actingAs($customer)->renderMobileBottomNav();

        $this->assertStringContainsString('href="'.route('dashboard').'"', $html);
        $this->assertStringContainsString('Profil', $html);
        $this->assertStringNotContainsString(route('customer.profile.edit'), $html);
    }

    public function test_caregiver_profile_tab_points_to_dashboard(): void
    {
        $caregiver = User::factory()->create([
            'role' => 'caregiver',
            'status' => 'active',
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $html = $this->actingAs($caregiver)->renderMobileBottomNav();

        $this->assertStringContainsString('href="'.route('dashboard').'"', $html);
        $this->assertStringContainsString('Profil', $html);
        $this->assertStringNotContainsString(route('caregiver.profile.edit'), $html);
    }

    private function renderMobileBottomNav(): string
    {
        return Blade::render('<x-mobile-bottom-nav />');
    }
}
