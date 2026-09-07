<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): array
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'name' => 'Budi Santoso',
        ]);
        $customer = Customer::create([
            'user_id' => $user->id,
            'address' => 'Jl. Melati No. 12, Jakarta',
            'patient_needs' => 'Pendampingan kontrol dokter',
            'emergency_contact' => 'Siti',
            'emergency_contact_phone' => '081234567890',
        ]);

        return [$user, $customer];
    }

    public function test_customer_can_view_profile_page(): void
    {
        [$user] = $this->makeCustomer();

        $response = $this->actingAs($user)->get('/customer/profile');

        $response->assertStatus(200);
        $response->assertSee('Profil Pasien / Keluarga');
        $response->assertSee('Kebutuhan Pasien');
        $response->assertSee('Kontak Darurat');
        $response->assertSee('flex flex-col items-center text-center gap-4');
        $response->assertSee('rounded-full bg-gradient-to-tr from-rose-100 to-rose-50');
        $response->assertSee('BS');
    }

    public function test_customer_can_update_profile(): void
    {
        [$user] = $this->makeCustomer();

        $response = $this->actingAs($user)->patch('/customer/profile', [
            'address' => 'Jl. Baru No. 5, Jakarta',
            'patient_needs' => 'Perawatan luka pasca operasi',
            'emergency_contact' => 'Andi',
            'emergency_contact_phone' => '081298765432',
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('customers', [
            'user_id' => $user->id,
            'address' => 'Jl. Baru No. 5, Jakarta',
            'patient_needs' => 'Perawatan luka pasca operasi',
            'emergency_contact' => 'Andi',
            'emergency_contact_phone' => '081298765432',
        ]);
    }

    public function test_caregiver_cannot_access_customer_profile(): void
    {
        $caregiver = User::factory()->create(['role' => 'caregiver']);

        $this->actingAs($caregiver)->get('/customer/profile')->assertForbidden();
    }
}
