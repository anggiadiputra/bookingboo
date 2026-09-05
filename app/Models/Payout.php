<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permintaan pencairan dana caregiver.
 */
class Payout extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_PAID => 'Dibayar',
        self::STATUS_REJECTED => 'Ditolak',
    ];

    protected $fillable = [
        'caregiver_id',
        'amount',
        'status',
        'note',
        'admin_note',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function label(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Total pendapatan caregiver yang eligible untuk dicairkan:
     * booking completed dengan pembayaran lunas, dikurangi komisi platform
     * dan dikurangi payout yang sudah diajukan/dibayar.
     */
    public static function eligibleAmount(Caregiver $caregiver): float
    {
        $earnings = Booking::query()
            ->where('caregiver_id', $caregiver->id)
            ->where('status', Booking::STATUS_COMPLETED)
            ->whereHas('payments', fn ($q) => $q->where('status', Payment::STATUS_PAID))
            ->get()
            ->sum(fn (Booking $b) => $b->caregiverEarnings());

        $withdrawn = (float) self::query()
            ->where('caregiver_id', $caregiver->id)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_PAID])
            ->sum('amount');

        return round(max(0, $earnings - $withdrawn), 2);
    }
}
