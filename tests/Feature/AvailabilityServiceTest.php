<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $service;

    private Caregiver $caregiver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AvailabilityService::class);
        $this->caregiver = $this->makeCaregiver();
    }

    private function makeCaregiver(): Caregiver
    {
        $user = User::factory()->create([
            'role' => 'caregiver',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        return Caregiver::create([
            'user_id' => $user->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'rating' => 4.8,
            'verification_status' => 'verified',
        ]);
    }

    private function makeCustomer(): Customer
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        return Customer::create(['user_id' => $user->id]);
    }

    private function addSchedule(string $start, string $end, string $status = 'available'): Schedule
    {
        return Schedule::create([
            'caregiver_id' => $this->caregiver->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
        ]);
    }

    private function addBooking(string $start, string $end, string $status = 'confirmed'): Booking
    {
        return Booking::create([
            'booking_code' => 'BB-TEST-'.uniqid(),
            'customer_id' => $this->makeCustomer()->id,
            'caregiver_id' => $this->caregiver->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
        ]);
    }

    public function test_range_in_the_past_is_invalid(): void
    {
        [$ok, $message] = $this->service->validateRange(
            Carbon::parse('2026-01-01 08:00'),
            Carbon::parse('2026-01-01 12:00')
        );

        $this->assertFalse($ok);
        $this->assertStringContainsString('masa lalu', $message);
    }

    public function test_range_end_before_start_is_invalid(): void
    {
        $start = now()->addDays(2)->setTime(10, 0);

        [$ok, $message] = $this->service->validateRange($start, $start->copy()->subHour());

        $this->assertFalse($ok);
        $this->assertStringContainsString('selesai', $message);
    }

    public function test_range_end_equal_start_is_invalid(): void
    {
        $start = now()->addDays(2)->setTime(10, 0);

        [$ok, $message] = $this->service->validateRange($start, $start->copy());

        $this->assertFalse($ok);
    }

    public function test_booking_within_available_schedule_is_valid(): void
    {
        $start = now()->addDays(2)->setTime(8, 0);
        $end = now()->addDays(2)->setTime(12, 0);
        $this->addSchedule('2026-01-01 00:00', '2099-01-01 00:00');

        [$ok, $message] = $this->service->validateBookingSlot($this->caregiver, $start, $end);

        $this->assertTrue($ok, $message);
    }

    public function test_booking_outside_schedule_is_invalid(): void
    {
        // Jadwal hanya 08-12, booking mulai 11 s.d. 14 -> tidak tercakup
        $start = now()->addDays(2)->setTime(11, 0);
        $end = now()->addDays(2)->setTime(14, 0);
        $this->addSchedule(now()->addDays(2)->format('Y-m-d').' 08:00', now()->addDays(2)->format('Y-m-d').' 12:00');

        [$ok, $message] = $this->service->validateBookingSlot($this->caregiver, $start, $end);

        $this->assertFalse($ok);
        $this->assertStringContainsString('tidak tersedia', $message);
    }

    public function test_contiguous_schedules_cover_full_range(): void
    {
        // Jadwal berdempet 08-12 dan 12-16 harus mencakup booking 08-16
        $day = now()->addDays(2)->format('Y-m-d');
        $this->addSchedule($day.' 08:00', $day.' 12:00');
        $this->addSchedule($day.' 12:00', $day.' 16:00');

        $start = now()->addDays(2)->setTime(8, 0);
        $end = now()->addDays(2)->setTime(16, 0);

        $this->assertTrue(
            $this->service->isWithinAvailableSchedule($this->caregiver, $start, $end)
        );
    }

    public function test_gap_between_schedules_breaks_coverage(): void
    {
        // Jadwal 08-11 dan 13-16: rentang 08-16 punya celah 11-13
        $day = now()->addDays(2)->format('Y-m-d');
        $this->addSchedule($day.' 08:00', $day.' 11:00');
        $this->addSchedule($day.' 13:00', $day.' 16:00');

        $start = now()->addDays(2)->setTime(8, 0);
        $end = now()->addDays(2)->setTime(16, 0);

        $this->assertFalse(
            $this->service->isWithinAvailableSchedule($this->caregiver, $start, $end)
        );
    }

    public function test_blocked_schedule_does_not_cover_range(): void
    {
        $day = now()->addDays(2)->format('Y-m-d');
        $this->addSchedule($day.' 08:00', $day.' 12:00', Schedule::STATUS_BLOCKED);

        $start = now()->addDays(2)->setTime(9, 0);
        $end = now()->addDays(2)->setTime(11, 0);

        $this->assertFalse(
            $this->service->isWithinAvailableSchedule($this->caregiver, $start, $end)
        );
    }

    public function test_overlapping_active_booking_is_detected(): void
    {
        // Booking aktif 08-12, cek rentang 10-14 -> overlap
        $day = now()->addDays(2)->format('Y-m-d');
        $this->addBooking($day.' 08:00', $day.' 12:00');

        $start = now()->addDays(2)->setTime(10, 0);
        $end = now()->addDays(2)->setTime(14, 0);

        $this->assertTrue(
            $this->service->hasOverlappingBooking($this->caregiver, $start, $end)
        );
    }

    public function test_completed_or_cancelled_booking_does_not_block(): void
    {
        // Booking selesai/dibatalkan tidak boleh memblokir waktu
        $day = now()->addDays(2)->format('Y-m-d');
        $this->addBooking($day.' 08:00', $day.' 12:00', 'completed');
        $this->addBooking($day.' 14:00', $day.' 16:00', 'cancelled');

        $start = now()->addDays(2)->setTime(9, 0);
        $end = now()->addDays(2)->setTime(15, 0);

        $this->assertFalse(
            $this->service->hasOverlappingBooking($this->caregiver, $start, $end)
        );
    }

    public function test_all_blocking_statuses_are_detected(): void
    {
        $day = now()->addDays(2)->format('Y-m-d');
        foreach (Booking::BLOCKING_STATUSES as $status) {
            $this->addBooking($day.' 08:00', $day.' 12:00', $status);

            $this->assertTrue(
                $this->service->hasOverlappingBooking(
                    $this->caregiver,
                    now()->addDays(2)->setTime(10, 0),
                    now()->addDays(2)->setTime(11, 0)
                ),
                "Status {$status} seharusnya memblokir"
            );

            $this->caregiver->bookings()->delete();
        }
    }

    public function test_back_to_back_booking_does_not_overlap(): void
    {
        // Booking aktif 08-12, booking baru 12-16 -> tidak overlap (berdempet boleh)
        $day = now()->addDays(2)->format('Y-m-d');
        $this->addBooking($day.' 08:00', $day.' 12:00');

        $start = now()->addDays(2)->setTime(12, 0);
        $end = now()->addDays(2)->setTime(16, 0);

        $this->assertFalse(
            $this->service->hasOverlappingBooking($this->caregiver, $start, $end)
        );
    }

    public function test_validate_booking_slot_rejects_overlap_and_outside_schedule(): void
    {
        $day = now()->addDays(2)->format('Y-m-d');
        $this->addSchedule($day.' 08:00', $day.' 12:00');
        $this->addBooking($day.' 09:00', $day.' 11:00');

        // Rentang 10-12: tersedia di jadwal tapi bentrok booking 09-11
        [$ok, $message] = $this->service->validateBookingSlot(
            $this->caregiver,
            now()->addDays(2)->setTime(10, 0),
            now()->addDays(2)->setTime(12, 0)
        );

        $this->assertFalse($ok);
        $this->assertStringContainsString('sudah memiliki booking', $message);
    }
}
