<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Profil pasien lengkap: field identitas & medis pasien, tampilan, dan simpan.
 */
class CustomerPatientProfileTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): array
    {
        $user = User::factory()->create(['role' => 'customer']);
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
            'emergency_contact_phone' => '081234567890',
        ]);

        return [$user, $customer];
    }

    public function test_profile_page_shows_complete_patient_info(): void
    {
        [$user] = $this->makeCustomer();

        $response = $this->actingAs($user)->get('/customer/profile');

        $response->assertStatus(200);
        $response->assertSee('Profil Pasien / Keluarga');
        $response->assertSee('Sukirman Santoso');
        $response->assertSee('Laki-laki');
        $response->assertSee('74 tahun');
        $response->assertSee('Darah A');
        $response->assertSee('Penisilin');
        $response->assertSee('Hipertensi & diabetes.');
        $response->assertSee('68.5');
        $response->assertSee('168');
        $response->assertSee('Kontak Darurat');
    }

    public function test_profile_page_shows_default_patient_name_when_empty(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'name' => 'Budi Santoso']);
        Customer::create(['user_id' => $user->id]);

        $this->actingAs($user)->get('/customer/profile')
            ->assertOk()
            ->assertSee('Budi Santoso');
    }

    public function test_customer_can_update_patient_details(): void
    {
        [$user, $customer] = $this->makeCustomer();

        $response = $this->actingAs($user)->patch('/customer/profile', [
            'patient_name' => 'Sukirman Santoso',
            'patient_birth_date' => '1950-01-01',
            'patient_gender' => 'male',
            'patient_blood_type' => 'B',
            'patient_allergies' => 'Penisilin, seafood',
            'patient_condition' => 'Hipertensi, diabetes, riwayat stroke.',
            'patient_weight_kg' => '70.5',
            'patient_height_cm' => '170',
            'address' => 'Jl. Melati No. 12, Jakarta',
            'patient_needs' => 'Pendampingan kontrol dokter',
            'emergency_contact' => 'Siti',
            'emergency_contact_phone' => '081234567890',
        ]);

        $response->assertSessionHas('status');

        $fresh = Customer::findOrFail($customer->id);
        $this->assertSame('1950-01-01', $fresh->patient_birth_date->format('Y-m-d'));
        $this->assertSame('B', $fresh->patient_blood_type);
        $this->assertSame('Penisilin, seafood', $fresh->patient_allergies);
        $this->assertSame(70.5, (float) $fresh->patient_weight_kg);
        $this->assertSame(170.0, (float) $fresh->patient_height_cm);
    }

    public function test_empty_patient_name_defaults_to_account_name(): void
    {
        [$user, $customer] = $this->makeCustomer();

        $this->actingAs($user)->patch('/customer/profile', [
            'patient_name' => '',
            'patient_birth_date' => null,
            'patient_gender' => '',
            'patient_blood_type' => '',
            'patient_allergies' => null,
            'patient_condition' => null,
            'patient_weight_kg' => null,
            'patient_height_cm' => null,
            'address' => $customer->address,
            'patient_needs' => $customer->patient_needs,
            'emergency_contact' => $customer->emergency_contact,
            'emergency_contact_phone' => $customer->emergency_contact_phone,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'patient_name' => $user->name,
        ]);
    }

    public function test_patient_details_validation(): void
    {
        [$user] = $this->makeCustomer();

        $this->actingAs($user)->patch('/customer/profile', [
            'patient_birth_date' => '2099-01-01',
            'patient_gender' => 'unknown',
            'patient_blood_type' => 'X',
            'patient_weight_kg' => '500',
            'patient_height_cm' => '10',
        ])->assertSessionHasErrors([
            'patient_birth_date', 'patient_gender', 'patient_blood_type', 'patient_weight_kg', 'patient_height_cm',
        ]);
    }

    public function test_navigation_points_customer_to_patient_profile(): void
    {
        [$user] = $this->makeCustomer();

        // Dashboard customer memuat tautan "Profil Pasien"
        $this->actingAs($user)->get('/dashboard')->assertSee('Profil Pasien');
    }
}
