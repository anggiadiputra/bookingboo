<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;

/**
 * Alur check-in / check-out layanan oleh caregiver.
 *
 * - Check-in: hanya dari status confirmed, saat waktu layanan sudah dimulai.
 * - Check-out: hanya setelah check-in, dari status in_progress.
 */
class CheckinService
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * @return array{0: bool, 1: string}
     */
    public function checkIn(Booking $booking, User $actor): array
    {
        if ($booking->status !== Booking::STATUS_CONFIRMED) {
            return [false, 'Check-in hanya dapat dilakukan pada booking yang sudah dikonfirmasi.'];
        }

        if ($booking->check_in_at) {
            return [false, 'Check-in sudah dilakukan sebelumnya.'];
        }

        if (now()->lt($booking->start_time)) {
            return [false, 'Check-in hanya dapat dilakukan setelah waktu layanan dimulai ('.
                $booking->start_time->format('d M Y, H:i').').'];
        }

        if (now()->gt($booking->end_time->addHours(config('booking.checkin.grace_hours', 2)))) {
            return [false, 'Batas waktu check-in telah lewat. Hubungi admin untuk bantuan.'];
        }

        $checkInAt = now();

        // Transisi atomik agar klik ganda/dua tab tidak mencatat check-in dobel.
        $affected = Booking::query()
            ->whereKey($booking->id)
            ->where('status', Booking::STATUS_CONFIRMED)
            ->whereNull('check_in_at')
            ->update([
                'check_in_at' => $checkInAt,
                'status' => Booking::STATUS_IN_PROGRESS,
            ]);

        if ($affected === 0) {
            return [false, 'Check-in sudah dilakukan atau status booking berubah.'];
        }

        $booking->refresh();

        $booking->statusHistories()->create([
            'from_status' => Booking::STATUS_CONFIRMED,
            'to_status' => Booking::STATUS_IN_PROGRESS,
            'changed_by' => $actor->id,
            'note' => 'Check-in oleh caregiver.',
        ]);

        $this->notifications->attendanceUpdated($booking, true);

        return [true, 'Check-in berhasil. Layanan sedang berlangsung.'];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public function checkOut(Booking $booking, User $actor): array
    {
        if (! $booking->check_in_at) {
            return [false, 'Check-in belum dilakukan.'];
        }

        if ($booking->check_out_at) {
            return [false, 'Check-out sudah dilakukan sebelumnya.'];
        }

        if ($booking->status !== Booking::STATUS_IN_PROGRESS) {
            return [false, 'Check-out hanya dapat dilakukan saat layanan berlangsung.'];
        }

        $checkOut = now();

        // Transisi atomik agar dua check-out simultan tidak mencatat dobel.
        $affected = Booking::query()
            ->whereKey($booking->id)
            ->where('status', Booking::STATUS_IN_PROGRESS)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->update([
                'check_out_at' => $checkOut,
                'status' => Booking::STATUS_COMPLETED,
            ]);

        if ($affected === 0) {
            return [false, 'Check-out hanya dapat dilakukan saat layanan berlangsung.'];
        }

        $booking->refresh();

        $overtime = max(0, (int) round($booking->check_in_at->diffInMinutes($checkOut))
            - (int) $booking->start_time->diffInMinutes($booking->end_time));

        $booking->update(['overtime_minutes' => $overtime]);

        $booking->statusHistories()->create([
            'from_status' => Booking::STATUS_IN_PROGRESS,
            'to_status' => Booking::STATUS_COMPLETED,
            'changed_by' => $actor->id,
            'note' => 'Check-out oleh caregiver.',
        ]);

        $this->notifications->attendanceUpdated($booking, false);

        return [true, 'Check-out berhasil. Layanan selesai.'];
    }
}
