<?php

namespace Tests\Feature;

use App\Models\Caregiver;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CaregiverScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function makeCaregiverSession(): array
    {
        $user = User::factory()->create([
            'role' => 'caregiver',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $caregiver = Caregiver::create([
            'user_id' => $user->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'rating' => 4.8,
            'verification_status' => 'verified',
        ]);

        return [$user, $caregiver];
    }

    public function test_caregiver_can_add_schedule(): void
    {
        [$user] = $this->makeCaregiverSession();

        $response = $this->actingAs($user)->post(route('caregiver.schedules.store'), [
            'start_time' => now()->addDays(3)->format('Y-m-d').'T08:00',
            'end_time' => now()->addDays(3)->format('Y-m-d').'T12:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_overlapping_schedule_is_rejected(): void
    {
        [$user, $caregiver] = $this->makeCaregiverSession();
        $day = now()->addDays(3)->format('Y-m-d');

        Schedule::create([
            'caregiver_id' => $caregiver->id,
            'start_time' => $day.' 08:00:00',
            'end_time' => $day.' 12:00:00',
            'status' => Schedule::STATUS_AVAILABLE,
        ]);

        $response = $this->actingAs($user)->post(route('caregiver.schedules.store'), [
            'start_time' => $day.'T10:00',
            'end_time' => $day.'T14:00',
        ]);

        $response->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_back_to_back_schedule_is_allowed(): void
    {
        [$user, $caregiver] = $this->makeCaregiverSession();
        $day = now()->addDays(3)->format('Y-m-d');

        Schedule::create([
            'caregiver_id' => $caregiver->id,
            'start_time' => $day.' 08:00:00',
            'end_time' => $day.' 12:00:00',
            'status' => Schedule::STATUS_AVAILABLE,
        ]);

        $response = $this->actingAs($user)->post(route('caregiver.schedules.store'), [
            'start_time' => $day.'T12:00',
            'end_time' => $day.'T16:00',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('schedules', 2);
    }

    public function test_non_caregiver_cannot_add_schedule(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);

        $response = $this->actingAs($customer)->post(route('caregiver.schedules.store'), [
            'start_time' => now()->addDays(3)->format('Y-m-d').'T08:00',
            'end_time' => now()->addDays(3)->format('Y-m-d').'T12:00',
        ]);

        $response->assertForbidden();
    }

    public function test_schedule_add_declined_while_lock_is_held(): void
    {
        [$user, $caregiver] = $this->makeCaregiverSession();
        $day = now()->addDays(3)->format('Y-m-d');

        $lock = Cache::lock('schedule:'.$caregiver->id, 15);
        $lock->get();

        $response = $this->actingAs($user)->post(route('caregiver.schedules.store'), [
            'start_time' => $day.'T08:00',
            'end_time' => $day.'T12:00',
        ]);

        $lock->release();

        $response->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_cannot_delete_booked_schedule(): void
    {
        [$user, $caregiver] = $this->makeCaregiverSession();
        $day = now()->addDays(3)->format('Y-m-d');

        $schedule = Schedule::create([
            'caregiver_id' => $caregiver->id,
            'start_time' => $day.' 08:00:00',
            'end_time' => $day.' 12:00:00',
            'status' => Schedule::STATUS_BOOKED,
        ]);

        $response = $this->actingAs($user)->delete(route('caregiver.schedules.destroy', $schedule));

        $response->assertSessionHasErrors('schedule');
        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
    }

    public function test_cannot_delete_other_caregiver_schedule(): void
    {
        [$user] = $this->makeCaregiverSession();

        $otherUser = User::factory()->create([
            'role' => 'caregiver',
            'status' => 'active',
            'approved_at' => now(),
        ]);
        $otherCaregiver = Caregiver::create([
            'user_id' => $otherUser->id,
            'skills' => 'Terapi wicara',
            'service_area' => 'Bandung',
            'hourly_rate' => 40000,
            'rating' => 4.0,
            'verification_status' => 'verified',
        ]);

        $day = now()->addDays(3)->format('Y-m-d');
        $schedule = Schedule::create([
            'caregiver_id' => $otherCaregiver->id,
            'start_time' => $day.' 08:00:00',
            'end_time' => $day.' 12:00:00',
            'status' => Schedule::STATUS_AVAILABLE,
        ]);

        $this->actingAs($user)->delete(route('caregiver.schedules.destroy', $schedule))->assertForbidden();
        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
    }
}
