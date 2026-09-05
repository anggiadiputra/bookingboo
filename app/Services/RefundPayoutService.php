<?php

namespace App\Services;

use App\Http\Controllers\ComplaintController;
use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Complaint;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Refund customer saat pembatalan (MVP: pencatatan dana refund di payment)
 * dan pencairan dana caregiver (payout).
 */
class RefundPayoutService
{
    public function __construct(private NotificationService $notifications, private AuditLogService $audit) {}

    /**
     * Catat refund pada payment yang sudah lunas setelah pembatalan booking.
     * Dipanggil saat pembatalan customer/caregiver pada booking berstatus paid.
     *
     * @return array{0: bool, 1: string}
     */
    public function recordRefund(Booking $booking, float $refundAmount): array
    {
        $payment = $booking->payments()->where('status', Payment::STATUS_PAID)->latest()->first();

        if (! $payment) {
            return [false, 'Tidak ada pembayaran lunas untuk booking ini.'];
        }

        if ($refundAmount <= 0) {
            return [true, 'Tidak ada refund yang perlu dicatat.'];
        }

        // Transisi atomik: hanya mencatat sekali (refunded_amount awalnya 0).
        // Dua pembatalan/staf yang simultan tidak akan mencatat refund dobel.
        $updated = Payment::query()
            ->whereKey($payment->id)
            ->where('status', Payment::STATUS_PAID)
            ->where('refunded_amount', 0)
            ->update([
                'refunded_amount' => $refundAmount,
                'status' => Payment::STATUS_REFUNDED,
            ]);

        if ($updated === 0) {
            return [false, 'Refund sudah tercatat sebelumnya.'];
        }

        $payment->refresh();

        $this->audit->logSystem('refund.recorded',
            "Refund dicatat pada {$payment->payment_code}: Rp ".number_format($refundAmount, 0, ',', '.').
            ' (booking '.$booking->booking_code.').',
            $booking,
            ['payment_id' => $payment->id, 'payment_code' => $payment->payment_code, 'refund_amount' => $refundAmount]);

        return [true, 'Refund dicatat: Rp '.number_format($refundAmount, 0, ',', '.').'.'];
    }

    /**
     * Caregiver mengajukan pencairan sejumlah dana eligible.
     *
     * @return array{0: Payout|null, 1: string}
     */
    public function requestPayout(Caregiver $caregiver, ?string $note): array
    {
        // Serialisasi agar dua pengajuan simultan tidak membuat payout dobel
        // dari dana eligible yang sama.
        $lock = Cache::lock('payout:request:'.$caregiver->id, 15);

        if (! $lock->get()) {
            return [null, 'Pengajuan pencairan sedang diproses. Silakan coba lagi.'];
        }

        try {
            $eligible = Payout::eligibleAmount($caregiver);

            if ($eligible <= 0) {
                return [null, 'Belum ada dana yang dapat dicairkan.'];
            }

            if (Payout::query()
                ->where('caregiver_id', $caregiver->id)
                ->where('status', Payout::STATUS_PENDING)
                ->exists()) {
                return [null, 'Masih ada pengajuan pencairan yang menunggu diproses admin.'];
            }

            // UC-08: pencairan ditahan saat ada sengketa (komplain aktif) yang berjalan.
            if (self::hasOpenDispute($caregiver->id)) {
                return [null, 'Pencairan ditahan karena masih ada komplain/sengketa yang sedang berjalan. Dana akan tersedia setelah sengketa selesai.'];
            }

            $payout = Payout::create([
                'caregiver_id' => $caregiver->id,
                'amount' => $eligible,
                'status' => Payout::STATUS_PENDING,
                'note' => $note,
            ]);

            $this->audit->logSystem('payout.requested',
                "Caregiver {$caregiver->user->name} mengajukan pencairan Rp ".number_format($eligible, 0, ',', '.').'.',
                $payout,
                ['payout_id' => $payout->id, 'caregiver_id' => $caregiver->id, 'amount' => $eligible]);

            return [$payout, 'Pengajuan pencairan berhasil dibuat. Menunggu diproses admin.'];
        } finally {
            $lock->release();
        }
    }

    /**
     * Admin memproses pengajuan pencairan (bayar atau tolak).
     *
     * @return array{0: bool, 1: string}
     */
    public function processPayout(Payout $payout, string $action, User $admin, string $adminNote = ''): array
    {
        if ($payout->status !== Payout::STATUS_PENDING) {
            return [false, 'Pengajuan ini sudah diproses sebelumnya.'];
        }

        [$ok, $message] = DB::transaction(function () use ($payout, $action, $admin, $adminNote) {
            // Kunci baris payout: dua admin yang memproses payout yang sama secara
            // simultan akan serial, baris kedua lalu gagal karena status sudah berubah.
            $locked = Payout::query()
                ->whereKey($payout->id)
                ->where('status', Payout::STATUS_PENDING)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return [false, 'Pengajuan ini sudah diproses sebelumnya.'];
            }

            if ($action === 'pay') {
                // Pendapatan harus mencukupi; payout pending lain dihitung sebagai dana terpakai.
                $caregiver = $locked->caregiver;
                $earnings = Booking::query()
                    ->where('caregiver_id', $caregiver->id)
                    ->where('status', Booking::STATUS_COMPLETED)
                    ->whereHas('payments', fn ($q) => $q->where('status', Payment::STATUS_PAID))
                    ->get()
                    ->sum(fn (Booking $b) => $b->caregiverEarnings());

                $reserved = (float) Payout::query()
                    ->where('caregiver_id', $caregiver->id)
                    ->where('id', '!=', $payout->id)
                    ->whereIn('status', [Payout::STATUS_PENDING, Payout::STATUS_PAID])
                    ->sum('amount');

                if ($earnings - $reserved < (float) $payout->amount) {
                    return [false, 'Dana eligible tidak mencukupi untuk pencairan ini.'];
                }

                // UC-08: payout pending tidak boleh dibayar saat sengketa masih berjalan.
                if (self::hasOpenDispute($caregiver->id)) {
                    return [false, 'Pencairan tidak dapat dibayar karena masih ada komplain/sengketa aktif pada booking caregiver ini.'];
                }
            }

            // Transisi atomik: hanya sukses bila masih pending.
            $affected = Payout::query()
                ->whereKey($payout->id)
                ->where('status', Payout::STATUS_PENDING)
                ->update([
                    'status' => $action === 'pay' ? Payout::STATUS_PAID : Payout::STATUS_REJECTED,
                    'admin_note' => $adminNote ?: null,
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                ]);

            if ($affected === 0) {
                return [false, 'Pengajuan ini sudah diproses sebelumnya.'];
            }

            $payout->refresh();

            $this->audit->log($admin, $action === 'pay' ? 'payout.paid' : 'payout.rejected',
                ($action === 'pay' ? 'Membayar pencairan' : 'Menolak pencairan').' #'.$payout->id.
                ' untuk '.$payout->caregiver->user->name.' sebesar Rp '.number_format((float) $payout->amount, 0, ',', '.').'.',
                $payout,
                ['payout_id' => $payout->id, 'caregiver_id' => $payout->caregiver_id,
                    'amount' => (float) $payout->amount, 'admin_note' => $adminNote ?: null]);

            return [true, $action === 'pay'
                ? 'Pencairan dibayar: Rp '.number_format($payout->amount, 0, ',', '.').'.'
                : 'Pengajuan pencairan ditolak.'];
        });

        if ($ok) {
            $this->notifications->payoutProcessed($payout);
        }

        return [$ok, $message];
    }

    /**
     * Cek apakah caregiver masih punya sengketa aktif:
     * komplain open/in_review pada booking completed miliknya.
     */
    public static function hasOpenDispute(int $caregiverId): bool
    {
        return Complaint::query()
            ->whereIn('status', ComplaintController::OPEN_STATUSES)
            ->whereHas('booking', function ($q) use ($caregiverId) {
                $q->where('caregiver_id', $caregiverId)
                    ->where('status', Booking::STATUS_COMPLETED);
            })
            ->exists();
    }
}
