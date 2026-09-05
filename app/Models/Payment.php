<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'booking_id',
        'payment_code',
        'amount',
        'refunded_amount',
        'platform_fee',
        'commission',
        'method',
        'status',
        'payment_type',
        'external_id',
        'payload',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'payload' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Buat kode payment unik dengan format PAY-YYYYMMDD-XXXXX.
     */
    public static function generateCode(): string
    {
        do {
            $code = 'PAY-'.now()->format('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        } while (static::where('payment_code', $code)->exists());

        return $code;
    }
}
