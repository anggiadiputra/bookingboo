<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Schedule;
use Carbon\CarbonInterface;

/**
 * Validasi ketersediaan caregiver dan pencegahan jadwal ganda (double booking).
 */
class AvailabilityService
{
    /**
     * Rentang valid: mulai di masa depan, akhir setelah mulai.
     *
     * @return array{0: bool, 1: string}
     */
    public function validateRange(CarbonInterface $start, CarbonInterface $end): array
    {
        if ($start->isPast()) {
            return [false, 'Waktu mulai tidak boleh di masa lalu.'];
        }

        if ($end->lessThanOrEqualTo($start)) {
            return [false, 'Waktu selesai harus setelah waktu mulai.'];
        }

        return [true, ''];
    }

    /**
     * Apakah caregiver punya jadwal kerja (status available) yang mencakup
     * penuh rentang [start, end]? Jadwal berdempet (08-12 dan 12-16) tetap dianggap
     * mencakup rentang 08-16.
     */
    public function isWithinAvailableSchedule(Caregiver $caregiver, CarbonInterface $start, CarbonInterface $end): bool
    {
        // Cari jadwal available yang menutupi posisi cursor, lalu maju ke ujung jadwal
        // hingga rentang tercakup penuh (mengejar jadwal yang berdempet).
        $cursor = $start->copy();

        while ($cursor->lessThan($end)) {
            $schedule = $caregiver->schedules()
                ->where('status', Schedule::STATUS_AVAILABLE)
                ->where('start_time', '<=', $cursor)
                ->where('end_time', '>', $cursor)
                ->orderByDesc('end_time')
                ->first();

            if (! $schedule) {
                return false;
            }

            $cursor = $schedule->end_time->copy();
        }

        return true;
    }

    /**
     * Apakah caregiver punya booking aktif (requested/accepted/confirmed/in_progress)
     * yang tumpang tindih dengan rentang [start, end]?
     */
    public function hasOverlappingBooking(Caregiver $caregiver, CarbonInterface $start, CarbonInterface $end): bool
    {
        return $caregiver->bookings()
            ->whereIn('status', Booking::BLOCKING_STATUSES)
            ->overlapping($start, $end)
            ->exists();
    }

    /**
     * Validasi lengkap satu rentang booking untuk caregiver.
     *
     * @return array{0: bool, 1: string} [ok, pesan error (kosong jika ok)]
     */
    public function validateBookingSlot(Caregiver $caregiver, CarbonInterface $start, CarbonInterface $end): array
    {
        [$ok, $message] = $this->validateRange($start, $end);
        if (! $ok) {
            return [false, $message];
        }

        if (! $this->isWithinAvailableSchedule($caregiver, $start, $end)) {
            return [false, 'Caregiver tidak tersedia pada rentang waktu yang dipilih.'];
        }

        if ($this->hasOverlappingBooking($caregiver, $start, $end)) {
            return [false, 'Caregiver sudah memiliki booking pada rentang waktu yang dipilih.'];
        }

        return [true, ''];
    }
}
