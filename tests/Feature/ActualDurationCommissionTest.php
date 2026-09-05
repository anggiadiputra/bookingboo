<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Perhitungan durasi aktual, komisi platform, dan pendapatan caregiver.
 */
class ActualDurationCommissionTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(array $overrides = []): Booking
    {
        $customerUser = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create(['user_id' => $customerUser->id]);
        $caregiverUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);

        $caregiver = Caregiver::create([
            'user_id' => $caregiverUser->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'verification_status' => 'verified',
        ]);

        return Booking::create(array_merge([
            'booking_code' => Booking::generateCode(),
            'customer_id' => $customerUser->customer->id,
            'caregiver_id' => $caregiver->id,
            'start_time' => '2026-09-01 09:00:00',
            'end_time' => '2026-09-01 12:00:00',
            'status' => Booking::STATUS_CONFIRMED,
            'total_amount' => 150000,
        ], $overrides));
    }

    public function test_billable_total_uses_scheduled_duration_before_checkout(): void
    {
        $booking = $this->makeBooking();

        $this->assertSame(180, $booking->billableMinutes());
        $this->assertSame(150000.0, $booking->billableTotal());
    }

    public function test_billable_total_uses_actual_duration_after_checkout(): void
    {
        // Aktual 3.5 jam -> dibulatkan 4 jam
        $booking = $this->makeBooking([
            'status' => Booking::STATUS_COMPLETED,
            'check_in_at' => '2026-09-01 09:00:00',
            'check_out_at' => '2026-09-01 12:30:00',
            'overtime_minutes' => 30,
        ]);

        $this->assertSame(210, $booking->billableMinutes());
        $this->assertSame(200000.0, $booking->billableTotal());
        $this->assertSame(30, $booking->overtime_minutes);
    }

    public function test_platform_commission_is_20_percent_of_billable_total(): void
    {
        $booking = $this->makeBooking([
            'status' => Booking::STATUS_COMPLETED,
            'check_in_at' => '2026-09-01 09:00:00',
            'check_out_at' => '2026-09-01 12:30:00',
        ]);

        $this->assertSame(40000.0, $booking->platformCommission());
        $this->assertSame(160000.0, $booking->caregiverEarnings());
        $this->assertEquals(
            $booking->billableTotal(),
            $booking->platformCommission() + $booking->caregiverEarnings(),
        );
    }

    public function test_check_out_records_overtime_minutes(): void
    {
        $booking = $this->makeBooking([
            'status' => Booking::STATUS_IN_PROGRESS,
            'start_time' => now()->subHours(4),
            'end_time' => now()->subHour(),
            'check_in_at' => now()->subHours(4),
        ]);
        $user = User::find($booking->caregiver->user_id);

        $this->actingAs($user)
            ->post(route('caregiver.bookings.check-out', $booking))
            ->assertRedirect()
            ->assertSessionHas('status');

        $booking->refresh();
        $this->assertSame(Booking::STATUS_COMPLETED, $booking->status);
        $this->assertGreaterThan(0, $booking->overtime_minutes);
    }

    public function test_invoice_shows_actual_duration_and_earnings_for_caregiver(): void
    {
        $booking = $this->makeBooking([
            'status' => Booking::STATUS_COMPLETED,
            'check_in_at' => '2026-09-01 09:00:00',
            'check_out_at' => '2026-09-01 12:30:00',
            'overtime_minutes' => 30,
        ]);
        $user = User::find($booking->caregiver->user_id);

        $this->actingAs($user)
            ->get(route('caregiver.bookings.invoice', $booking))
            ->assertOk()
            ->assertSee('Aktual:')
            ->assertSee('Lembur 30 menit')
            ->assertSee('Rincian Pendapatan Caregiver')
            ->assertSee('Rp 200.000')
            ->assertSee('Rp 40.000')
            ->assertSee('Rp 160.000');
    }

    public function test_invoice_hides_earnings_from_customer(): void
    {
        $booking = $this->makeBooking([
            'status' => Booking::STATUS_COMPLETED,
            'check_in_at' => '2026-09-01 09:00:00',
            'check_out_at' => '2026-09-01 12:30:00',
        ]);
        $user = User::find($booking->customer->user_id);

        $this->actingAs($user)
            ->get(route('customer.bookings.invoice', $booking))
            ->assertOk()
            ->assertSee('Aktual:')
            ->assertDontSee('Rincian Pendapatan Caregiver')
            ->assertDontSee('Komisi platform');
    }

    public function test_invoice_shows_scheduled_time_without_actual(): void
    {
        $booking = $this->makeBooking();
        $user = User::find($booking->customer->user_id);

        $this->actingAs($user)
            ->get(route('customer.bookings.invoice', $booking))
            ->assertOk()
            ->assertSee('09:00 - 12:00')
            ->assertDontSee('Aktual:');
    }
}
