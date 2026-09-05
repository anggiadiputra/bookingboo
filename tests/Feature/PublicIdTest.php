<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_gets_cus_public_id(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->assertMatchesRegularExpression('/^CUS-\d{6}$/', $user->public_id);
    }

    public function test_caregiver_gets_crg_public_id(): void
    {
        $user = User::factory()->create(['role' => 'caregiver']);

        $this->assertMatchesRegularExpression('/^CRG-\d{6}$/', $user->public_id);
    }

    public function test_staff_gets_stf_public_id(): void
    {
        foreach (['support', 'finance', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertMatchesRegularExpression('/^STF-\d{6}$/', $user->public_id);
        }
    }

    public function test_public_ids_are_unique(): void
    {
        $ids = collect(range(1, 50))->map(fn () => User::factory()->create()->public_id);

        $this->assertSame($ids->count(), $ids->unique()->count());
    }

    public function test_public_id_is_generated_on_creation(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->public_id);
    }
}
