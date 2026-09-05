<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat perubahan status booking (audit trail).
 */
class BookingStatusHistory extends Model
{
    protected $fillable = [
        'booking_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function labelFrom(): string
    {
        return Booking::STATUS_LABELS[$this->from_status] ?? $this->from_status;
    }

    public function labelTo(): string
    {
        return Booking::STATUS_LABELS[$this->to_status] ?? $this->to_status;
    }
}
