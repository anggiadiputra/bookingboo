<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_BOOKED = 'booked';

    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'caregiver_id',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }

    /**
     * Rentang waktu [start, end] tumpang tindih dengan jadwal ini.
     * Interval yang bersinggungan di batas (mis. 08-12 dan 12-16) tidak dianggap overlap.
     */
    public function scopeOverlapping($query, $start, $end)
    {
        return $query->where('start_time', '<', $end)
            ->where('end_time', '>', $start);
    }

    /**
     * Jadwal dengan status tersedia.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    /**
     * Apakah rentang [start, end] masih tersedia (belum booked/blocked)?
     */
    public function isAvailableForRange(CarbonInterface $start, CarbonInterface $end): bool
    {
        return ! $this->where('caregiver_id', $this->caregiver_id)
            ->where('status', '!=', self::STATUS_AVAILABLE)
            ->overlapping($start, $end)
            ->exists();
    }

    /**
     * Sinkronkan status jadwal: booked jika ada booking aktif yang mencakupnya,
     * available jika tidak ada.
     */
    public function syncStatus(): void
    {
        $hasActiveBooking = $this->caregiver->bookings()
            ->whereIn('status', Booking::BLOCKING_STATUSES)
            ->overlapping($this->start_time, $this->end_time)
            ->exists();

        $this->update([
            'status' => $hasActiveBooking ? self::STATUS_BOOKED : self::STATUS_AVAILABLE,
        ]);
    }
}
