<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan audit untuk aktivitas penting platform (login, verifikasi,
 * pembayaran, payout, moderasi) — read-only setelah dibuat.
 */
class AuditLog extends Model
{
    public const ACTOR_SYSTEM = 'system';

    public const ACTION_LABELS = [
        'auth.login' => 'Login',
        'auth.logout' => 'Logout',
        'auth.register' => 'Registrasi akun',
        'auth.suspended_login_attempt' => 'Percobaan login akun nonaktif',
        'staff.created' => 'Buat akun staf',
        'staff.approved' => 'Setujui staf',
        'staff.rejected' => 'Tolak staf',
        'caregiver.verified' => 'Verifikasi caregiver',
        'caregiver.rejected' => 'Tolak caregiver',
        'caregiver.document_approved' => 'Setujui dokumen caregiver',
        'caregiver.document_rejected' => 'Tolak dokumen caregiver',
        'booking.status_changed' => 'Perubahan status booking',
        'payment.settled' => 'Pembayaran diterima',
        'payment.failed' => 'Pembayaran gagal',
        'refund.recorded' => 'Refund dicatat',
        'payout.requested' => 'Pengajuan pencairan',
        'payout.paid' => 'Pencairan dibayar',
        'payout.rejected' => 'Pencairan ditolak',
        'dispute.refunded' => 'Refund sengketa',
        'dispute.payout_cancelled' => 'Batalkan payout sengketa',
        'review.hidden' => 'Sembunyikan review',
        'review.unhidden' => 'Tampilkan review',
        'user.suspended' => 'Nonaktifkan akun',
        'user.reactivated' => 'Aktifkan kembali akun',
    ];

    protected $fillable = [
        'user_id',
        'actor_role',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    /**
     * Judul subjek (model terkait) untuk tampilan panel admin.
     */
    public function subjectLabel(): string
    {
        if (! $this->subject_type || ! $this->subject_id) {
            return '-';
        }

        $model = $this->subject_type::find($this->subject_id);

        if (! $model) {
            return '#'.$this->subject_id;
        }

        return match ($this->subject_type) {
            User::class => $model->name.' ('.($model->public_id ?? '-').')',
            Booking::class => $model->booking_code,
            Review::class => 'Review #'.$model->id,
            Payout::class => 'Payout #'.$model->id,
            Caregiver::class => $model->user->name ?? 'Caregiver #'.$model->id,
            CaregiverDocument::class => 'Dokumen ('.($model->type ?? '-').')',
            default => $this->subject_type.' #'.$this->subject_id,
        };
    }
}
