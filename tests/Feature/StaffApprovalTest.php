<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    private function makePendingStaff(): User
    {
        return User::factory()->create([
            'role' => 'support',
            'status' => 'pending',
            'approved_at' => null,
        ]);
    }

    public function test_admin_can_view_staff_list(): void
    {
        $admin = $this->makeAdmin();
        $this->makePendingStaff();

        $response = $this->actingAs($admin)->get('/admin/staff');

        $response->assertStatus(200);
        $response->assertSee('Manajemen Staf');
    }

    public function test_admin_can_create_pending_staff(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post('/admin/staff', [
            'name' => 'Staf Baru',
            'email' => 'stafbaru@bookingboo.test',
            'phone' => '081234567890',
            'role' => 'finance',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('admin.staff.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'stafbaru@bookingboo.test',
            'role' => 'finance',
            'status' => 'pending',
            'approved_at' => null,
        ]);
    }

    public function test_admin_can_approve_pending_staff(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makePendingStaff();

        $response = $this->actingAs($admin)->patch("/admin/staff/{$staff->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'status' => 'active',
        ]);
        $this->assertNotNull($staff->fresh()->approved_at);
    }

    public function test_admin_can_reject_pending_staff(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makePendingStaff();

        $response = $this->actingAs($admin)->patch("/admin/staff/{$staff->id}/reject");

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'status' => 'rejected',
        ]);
        $this->assertNull($staff->fresh()->approved_at);
    }

    public function test_pending_staff_cannot_access_dashboard(): void
    {
        $staff = $this->makePendingStaff();

        $this->actingAs($staff)->get('/dashboard')->assertForbidden();
    }

    public function test_approved_staff_can_access_dashboard(): void
    {
        $staff = User::factory()->create([
            'role' => 'support',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        // Staf disetujui diarahkan ke dashboard khusus perannya
        $this->actingAs($staff)->get('/dashboard')
            ->assertRedirect(route('support.dashboard'));

        $this->actingAs($staff)->get(route('support.dashboard'))->assertOk();
    }

    public function test_non_admin_cannot_access_staff_management(): void
    {
        $support = User::factory()->create([
            'role' => 'support',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->actingAs($support)->get('/admin/staff')->assertForbidden();
    }
}
