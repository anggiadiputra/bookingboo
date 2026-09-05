<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman detail customer/pasien untuk staf (support, finance, admin).
 */
class AdminCustomerShowTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomerWithProfile(): Customer
    {
        $user = User::factory()->create(['role' => 'customer', 'phone' => '081234567890']);
        $customer = Customer::create([
            'user_id' => $user->id,
            'patient_name' => 'Sukirman Santoso',
            'patient_birth_date' => '1952-03-14',
            'patient_gender' => 'male',
            'patient_blood_type' => 'A',
            'patient_allergies' => 'Penisilin',
            'patient_condition' => 'Hipertensi & diabetes.',
            'patient_weight_kg' => 68.5,
            'patient_height_cm' => 168,
            'address' => 'Jl. Melati No. 12, Jakarta',
            'patient_needs' => 'Pendampingan kontrol dokter',
            'emergency_contact' => 'Siti',
            'emergency_contact_phone' => '081298765432',
        ]);

        return $customer;
    }

    private function staff(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active', 'approved_at' => now()]);
    }

    public function test_staff_roles_can_view_customer_detail(): void
    {
        $customer = $this->makeCustomerWithProfile();

        foreach (['support', 'finance', 'admin'] as $role) {
            $this->actingAs($this->staff($role))
                ->get(route('admin.customers.show', $customer))
                ->assertOk()
                ->assertSee('Sukirman Santoso')
                ->assertSee('Penisilin')
                ->assertSee('Hipertensi & diabetes.')
                ->assertSee('Golongan Darah')
                ->assertSee('081234567890');
        }
    }

    public function test_customer_cannot_access_other_customer_detail(): void
    {
        $customer = $this->makeCustomerWithProfile();
        $other = User::factory()->create(['role' => 'customer']);

        $this->actingAs($other)->get(route('admin.customers.show', $customer))->assertForbidden();
    }

    public function test_customer_detail_shows_booking_history(): void
    {
        $customer = $this->makeCustomerWithProfile();
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
            'customer_id' => $customer->id,
            'caregiver_id' => $caregiver->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(3),
            'status' => Booking::STATUS_CONFIRMED,
            'total_amount' => 150000,
        ]);

        $this->actingAs($this->staff('admin'))
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('Riwayat Booking')
            ->assertSee('BB-');
    }
}
