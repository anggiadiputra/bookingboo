<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Caregiver;
use App\Models\Schedule;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Alur booking: pengajuan (customer), penerimaan/penolakan (caregiver).
 */
class BookingService
{
    public function __construct(private AvailabilityService $availability, private RefundPayoutService $refunds, private NotificationService $notifications) {}

    /**
     * Customer mengajukan booking baru.
     *
     * @return array{0: ?Booking, 1: string} [booking, pesan error (kosong jika sukses)]
     */
    public function createRequest(Caregiver $caregiver, User $customer, array $data): array
    {
        if (! $caregiver->isVerified()) {
            return [null, 'Caregiver belum terverifikasi.'];
        }

        $start = Carbon::parse($data['start_time']);
        $end = Carbon::parse($data['end_time']);

        [$ok, $message] = $this->availability->validateBookingSlot($caregiver, $start, $end);
        if (! $ok) {
            return [null, $message];
        }

        // Serialisasi antar request sehingga dua pengajuan simultan untuk caregiver
        // yang sama tidak lolos pengecekan tumpang tindih sekaligus (double booking).
        $lock = Cache::lock('booking:caregiver:'.$caregiver->id, 15);

        if (! $lock->get()) {
            return [null, 'Pengajuan sedang diproses. Silakan coba lagi.'];
        }

        try {
            $booking = DB::transaction(function () use ($caregiver, $customer, $start, $end, $data) {
                // Pengecekan ulang setelah lock diperoleh menutup celah race condition.
                if ($this->availability->hasOverlappingBooking($caregiver, $start, $end)) {
                    return null;
                }

                $booking = Booking::create([
                    'booking_code' => Booking::generateCode(),
                    'customer_id' => $customer->customer->id,
                    'caregiver_id' => $caregiver->id,
                    'start_time' => $start,
                    'end_time' => $end,
                    'location' => $data['location'] ?? null,
                    'needs' => $data['needs'] ?? null,
                    'status' => Booking::STATUS_REQUESTED,
                    'total_amount' => 0,
                ]);

                $this->markSchedulesBooked($caregiver, $start, $end);

                $this->recordHistory($booking, null, Booking::STATUS_REQUESTED, $customer, 'Booking diajukan oleh customer.');

                return $booking;
            });
        } finally {
            $lock->release();
        }

        if ($booking === null) {
            return [null, 'Caregiver sudah memiliki booking pada rentang waktu yang dipilih.'];
        }

        $this->notifications->bookingRequested($booking);

        return [$booking, ''];
    }

    /**
     * Caregiver menerima booking (requested -> accepted).
     *
     * @return array{0: bool, 1: string}
     */
    public function accept(Booking $booking, User $actor): array
    {
        // Transisi atomik: hanya sukses bila status masih 'requested'.
        $updated = Booking::query()
            ->whereKey($booking->id)
            ->where('status', Booking::STATUS_REQUESTED)
            ->update(['status' => Booking::STATUS_ACCEPTED]);

        if ($updated === 0) {
            return [false, 'Hanya booking dengan status menunggu konfirmasi yang dapat diterima.'];
        }

        $booking->refresh();
        $this->recordHistory($booking, Booking::STATUS_REQUESTED, Booking::STATUS_ACCEPTED, $actor);
        $this->notifications->bookingResponded($booking, true);

        return [true, 'Booking diterima.'];
    }

    /**
     * Caregiver menolak booking (requested -> rejected) dan melepas jadwal.
     *
     * @return array{0: bool, 1: string}
     */
    public function reject(Booking $booking, User $actor, string $reason): array
    {
        // Transisi atomik: hanya sukses bila status masih 'requested'.
        $updated = Booking::query()
            ->whereKey($booking->id)
            ->where('status', Booking::STATUS_REQUESTED)
            ->update([
                'status' => Booking::STATUS_REJECTED,
                'cancellation_reason' => $reason,
            ]);

        if ($updated === 0) {
            return [false, 'Hanya booking dengan status menunggu konfirmasi yang dapat ditolak.'];
        }

        $booking->refresh();
        $this->releaseSchedules($booking);
        $this->recordHistory($booking, Booking::STATUS_REQUESTED, Booking::STATUS_REJECTED, $actor, $reason);
        $this->notifications->bookingResponded($booking, false);

        return [true, 'Booking ditolak.'];
    }

    /**
     * Customer membatalkan booking (requested/accepted -> cancelled) dan melepas jadwal.
     *
     * @return array{0: bool, 1: string}
     */
    public function cancel(Booking $booking, User $actor, ?string $reason = null): array
    {
        return $this->doCancel(
            $booking,
            $actor,
            $reason,
            [Booking::STATUS_REQUESTED, Booking::STATUS_ACCEPTED],
            'Dibatalkan oleh customer.',
            'Booking hanya dapat dibatalkan sebelum dikonfirmasi.',
        );
    }

    /**
     * Caregiver membatalkan booking yang sudah disetujui (accepted/confirmed -> cancelled)
     * dan melepas jadwal.
     *
     * @return array{0: bool, 1: string}
     */
    public function cancelByCaregiver(Booking $booking, User $actor, ?string $reason = null): array
    {
        return $this->doCancel(
            $booking,
            $actor,
            $reason,
            [Booking::STATUS_ACCEPTED, Booking::STATUS_CONFIRMED],
            'Dibatalkan oleh caregiver.',
            'Booking hanya dapat dibatalkan sebelum layanan dimulai.',
            'caregiver',
        );
    }

    /**
     * Admin menugaskan caregiver pengganti untuk booking yang dibatalkan caregiver
     * (cancelled, needs_replacement -> requested). Jadwal caregiver baru ditandai booked.
     *
     * @return array{0: ?Booking, 1: string} [booking, pesan error (kosong jika sukses)]
     */
    public function assignReplacement(Booking $booking, Caregiver $replacement, User $actor): array
    {
        if ($booking->status !== Booking::STATUS_CANCELLED || ! $booking->needs_replacement) {
            return [null, 'Hanya booking yang dibatalkan caregiver dan menunggu pengganti yang dapat ditugaskan.'];
        }

        if ($replacement->id === $booking->caregiver_id) {
            return [null, 'Caregiver pengganti tidak boleh sama dengan caregiver sebelumnya.'];
        }

        if (! $replacement->isVerified()) {
            return [null, 'Caregiver pengganti belum terverifikasi.'];
        }

        [$slotOk] = $this->availability->validateBookingSlot($replacement, $booking->start_time, $booking->end_time);
        if (! $slotOk) {
            return [null, 'Caregiver pengganti tidak tersedia pada rentang waktu booking.'];
        }

        // Serialisasi agar dua admin tidak menugaskan pengganti berbeda secara simultan.
        $lock = Cache::lock('booking:replacement:'.$booking->id, 15);

        if (! $lock->get()) {
            return [null, 'Penugasan pengganti sedang diproses. Silakan coba lagi.'];
        }

        try {
            $booking = DB::transaction(function () use ($booking, $replacement, $actor) {
                // Verifikasi ulang setelah lock: status harus masih menunggu pengganti.
                if ($booking->fresh()->status !== Booking::STATUS_CANCELLED || ! $booking->fresh()->needs_replacement) {
                    return null;
                }

                // Pengecekan ulang setelah lock diperoleh menutup celah race condition.
                if ($this->availability->hasOverlappingBooking($replacement, $booking->start_time, $booking->end_time)) {
                    return null;
                }

                $booking->update([
                    'caregiver_id' => $replacement->id,
                    'status' => Booking::STATUS_REQUESTED,
                    'needs_replacement' => false,
                    'replacement_offered_at' => null,
                    // Reset data refund: pembatalan lama tidak lagi relevan untuk pengganti
                    'refund_percent' => 0,
                    'refund_amount' => 0,
                    'cancellation_fee' => 0,
                ]);

                $this->markSchedulesBooked($replacement, $booking->start_time, $booking->end_time);

                $this->recordHistory($booking, Booking::STATUS_CANCELLED, Booking::STATUS_REQUESTED, $actor,
                    "Caregiver pengganti ditugaskan: {$replacement->user->name}.");

                return $booking;
            });
        } finally {
            $lock->release();
        }

        if ($booking === null) {
            return [null, 'Caregiver pengganti sudah memiliki booking pada rentang waktu yang dipilih.'];
        }

        return [$booking, 'Caregiver pengganti berhasil ditugaskan.'];
    }

    /**
     * @param  list<string>  $allowedStatuses
     * @return array{0: bool, 1: string}
     */
    private function doCancel(
        Booking $booking,
        User $actor,
        ?string $reason,
        array $allowedStatuses,
        string $defaultReason,
        string $invalidMessage,
        string $party = 'customer',
    ): array {
        if (! in_array($booking->status, $allowedStatuses, true)) {
            return [false, $invalidMessage];
        }

        // Serialisasi pembatalan agar dua pembatalan simultan (cont. customer +
        // caregiver, atau klik ganda) tidak menghitung/dicatat refund dua kali.
        $lock = Cache::lock('booking:cancel:'.$booking->id, 15);

        if (! $lock->get()) {
            return [false, $invalidMessage];
        }

        try {
            return DB::transaction(function () use ($booking, $actor, $reason, $allowedStatuses, $defaultReason, $invalidMessage, $party) {
                // Verifikasi ulang setelah lock diperoleh.
                $booking = $booking->fresh();
                if (! in_array($booking->status, $allowedStatuses, true)) {
                    return [false, $invalidMessage];
                }

                $from = $booking->status;
                [$refundPercent, $refundAmount, $cancellationFee] = $this->computeRefund($booking, $party);

                // Pembatalan oleh caregiver yang sudah disetujui memicu penawaran caregiver pengganti.
                $needsReplacement = $party === 'caregiver' && in_array($from, [Booking::STATUS_ACCEPTED, Booking::STATUS_CONFIRMED], true);

                $booking->update([
                    'status' => Booking::STATUS_CANCELLED,
                    'cancellation_reason' => $reason ?? $defaultReason,
                    'cancelled_at' => now(),
                    'refund_percent' => $refundPercent,
                    'refund_amount' => $refundAmount,
                    'cancellation_fee' => $cancellationFee,
                    'needs_replacement' => $needsReplacement,
                    'replacement_offered_at' => $needsReplacement ? now() : null,
                ]);
                $this->releaseSchedules($booking);

                // Catat refund pada payment lunas (jika sudah dibayar sebelum pembatalan).
                if ($refundAmount > 0) {
                    $this->refunds->recordRefund($booking, (float) $refundAmount);
                }

                $this->recordHistory($booking, $from, Booking::STATUS_CANCELLED, $actor, $reason ?? $defaultReason);
                $this->notifications->bookingCancelled($booking, $reason ?? $defaultReason, (float) $refundAmount);

                return [true, 'Booking berhasil dibatalkan.'];
            });
        } finally {
            $lock->release();
        }
    }

    /**
     * Hitung refund dan biaya pembatalan sesuai kebijakan di config/booking.php.
     *
     * Customer: persen refund mengikuti batas waktu pembatalan (jam sebelum jadwal),
     * sisanya menjadi biaya pembatalan. Caregiver: refund penuh (kesalahan caregiver).
     *
     * @return array{0: int, 1: float, 2: float} [refund_percent, refund_amount, cancellation_fee]
     */
    private function computeRefund(Booking $booking, string $party): array
    {
        $total = (float) $booking->estimateTotal();

        if ($party === 'caregiver') {
            $percent = (int) config('booking.cancellation.caregiver_refund_percent', 100);

            return [$percent, round($total * $percent / 100, 2), 0.0];
        }

        $hoursBefore = (float) now()->diffInHours($booking->start_time, false);
        if ($hoursBefore < 0) {
            $hoursBefore = 0.0;
        }

        $percent = (int) config('booking.cancellation.default_refund_percent', 0);
        foreach (config('booking.cancellation.customer_refund_tiers', []) as $tier) {
            if ($hoursBefore >= $tier['hours']) {
                $percent = (int) $tier['percent'];
                break;
            }
        }

        $refundAmount = round($total * $percent / 100, 2);

        return [$percent, $refundAmount, round($total - $refundAmount, 2)];
    }

    /**
     * Tandai jadwal yang tercakup booking sebagai booked.
     */
    private function markSchedulesBooked(Caregiver $caregiver, CarbonInterface $start, CarbonInterface $end): void
    {
        $caregiver->schedules()
            ->available()
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->update(['status' => Schedule::STATUS_BOOKED]);
    }

    /**
     * Kembalikan jadwal yang tercakup booking ke available.
     */
    private function releaseSchedules(Booking $booking): void
    {
        $booking->caregiver->schedules()
            ->where('status', Schedule::STATUS_BOOKED)
            ->where('start_time', '<', $booking->end_time)
            ->where('end_time', '>', $booking->start_time)
            ->update(['status' => Schedule::STATUS_AVAILABLE]);
    }

    /**
     * Catat perubahan status ke riwayat.
     */
    private function recordHistory(Booking $booking, ?string $from, string $to, User $actor, ?string $note = null): void
    {
        BookingStatusHistory::create([
            'booking_id' => $booking->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $actor->id,
            'note' => $note,
        ]);
    }
}
