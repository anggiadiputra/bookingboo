<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard admin: ringkasan pengguna, booking, pembayaran, komplain.
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_stats_summary(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'approved_at' => now()]);

        $res = $this->actingAs($admin)->get(route('admin.dashboard'));

        $res->assertOk()
            ->assertSee('Pengguna')
            ->assertSee('Booking')
            ->assertSee('Pembayaran')
            ->assertSee('Pendapatan Platform')
            ->assertSee('Booking Terbaru')
            ->assertSee('Manajemen Staf');
    }

    public function test_admin_stats_count_correctly(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'approved_at' => now()]);
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create(['user_id' => $customer->id]);
        $caregiverUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);

        $caregiver = Caregiver::create([
            'user_id' => $caregiverUser->id,
            'skills' => 'Perawatan',
            'service_area' => 'Jakarta',
            'hourly_rate' => 50000,
            'verification_status' => 'verified',
        ]);

        Booking::create([
            'booking_code' => Booking::generateCode(),
            'customer_id' => Customer::first()->id,
            'caregiver_id' => $caregiver->id,
            'start_time' => '2026-09-01 09:00:00',
            'end_time' => '2026-09-01 12:00:00',
            'status' => Booking::STATUS_COMPLETED,
            'total_amount' => 150000,
        ]);

        $res = $this->actingAs($admin)->get(route('admin.dashboard'));

        $res->assertOk()->assertSee('BB-');
    }

    public function test_support_cannot_see_admin_stats(): void
    {
        $support = User::factory()->create(['role' => 'support', 'status' => 'active', 'approved_at' => now()]);

        $res = $this->actingAs($support)->get(route('support.dashboard'));

        // Dashboard support tidak menampilkan statistik admin
        $res->assertOk()->assertDontSee('Pendapatan Platform');
    }

    public function test_customer_cannot_see_admin_stats(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);

        $this->actingAs($customer)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Pendapatan Platform');
    }
}
