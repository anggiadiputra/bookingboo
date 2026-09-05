<?php

namespace App\Services;

use App\Models\Caregiver;
use App\Models\Review;
use App\Models\User;

class ModerationService
{
    public function __construct(private AuditLogService $audit) {}

    /**
     * Sembunyikan review (moderasi admin) dan hitung ulang rating caregiver.
     */
    public function hideReview(Review $review, User $staff, ?string $reason = null): void
    {
        if ($review->isPublished()) {
            $this->recalculateRating($review->reviewee_id);
        }

        $review->update([
            'status' => Review::STATUS_HIDDEN,
            'hidden_by' => $staff->id,
            'hidden_reason' => $reason,
            'hidden_at' => now(),
        ]);

        $this->recalculateRating($review->reviewee_id);

        $this->audit->log($staff, 'review.hidden',
            "Menyembunyikan review #{$review->id} dengan alasan: {$reason}.",
            $review,
            ['review_id' => $review->id, 'reason' => $reason, 'reviewee_id' => $review->reviewee_id]);
    }

    /**
     * Tampilkan kembali review yang disembunyikan dan hitung ulang rating.
     */
    public function unhideReview(Review $review, ?User $staff = null): void
    {
        if ($review->status === Review::STATUS_HIDDEN) {
            $review->update([
                'status' => Review::STATUS_PUBLISHED,
                'hidden_by' => null,
                'hidden_reason' => null,
                'hidden_at' => null,
            ]);

            $this->recalculateRating($review->reviewee_id);

            $this->audit->log($staff, 'review.unhidden',
                "Menampilkan kembali review #{$review->id}.",
                $review,
                ['review_id' => $review->id, 'reviewee_id' => $review->reviewee_id]);
        }
    }

    /**
     * Nonaktifkan akun user (bukan staf) sehingga tidak dapat login
     * dan menghilang dari daftar pencarian publik.
     */
    public function suspendUser(User $user, User $staff, string $reason): void
    {
        if ($user->isStaff()) {
            abort(403, 'Akun staf tidak dapat dinonaktifkan melalui moderasi akun.');
        }

        $user->update([
            'status' => User::STATUS_SUSPENDED,
            'suspended_by' => $staff->id,
            'suspended_reason' => $reason,
            'suspended_at' => now(),
        ]);

        $this->audit->log($staff, 'user.suspended',
            "Menonaktifkan akun {$user->name} ({$user->public_id}) dengan alasan: {$reason}.",
            $user,
            ['user_id' => $user->id, 'reason' => $reason]);
    }

    /**
     * Aktifkan kembali akun yang dinonaktifkan.
     */
    public function reactivateUser(User $user, ?User $staff = null): void
    {
        if ($user->status === User::STATUS_SUSPENDED) {
            $user->update([
                'status' => User::STATUS_ACTIVE,
                'suspended_by' => null,
                'suspended_reason' => null,
                'suspended_at' => null,
            ]);

            $this->audit->log($staff, 'user.reactivated',
                "Mengaktifkan kembali akun {$user->name} ({$user->public_id}).",
                $user,
                ['user_id' => $user->id]);
        }
    }

    /**
     * Hitung ulang rating rata-rata caregiver dari semua review publik yang tampil.
     */
    public function recalculateRating(int $caregiverUserId): void
    {
        $average = (float) Review::where('reviewee_id', $caregiverUserId)
            ->where('visibility', Review::VISIBILITY_PUBLIC)
            ->where('status', Review::STATUS_PUBLISHED)
            ->avg('rating');

        $caregiver = Caregiver::where('user_id', $caregiverUserId)->first();

        if ($caregiver) {
            $caregiver->update(['rating' => round($average, 2)]);
        }
    }
}
