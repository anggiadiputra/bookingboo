<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    public const STATUS_EXPIRED = 'expired';

    // Status yang masih memblokir waktu caregiver
    public const BLOCKING_STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_ACCEPTED,
        self::STATUS_CONFIRMED,
        self::STATUS_IN_PROGRESS,
    ];

    public const STATUS_LABELS = [
        self::STATUS_REQUESTED => 'Menunggu Konfirmasi',
        self::STATUS_ACCEPTED => 'Diterima',
        self::STATUS_CONFIRMED => 'Dikonfirmasi',
        self::STATUS_IN_PROGRESS => 'Berlangsung',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_REJECTED => 'Ditolak',
        self::STATUS_CANCELLED => 'Dibatalkan',
        self::STATUS_NO_SHOW => 'Tidak Hadir',
        self::STATUS_EXPIRED => 'Kedaluwarsa',
    ];

    protected $fillable = [
        'booking_code',
        'customer_id',
        'caregiver_id',
        'start_time',
        'end_time',
        'location',
        'needs',
        'status',
        'total_amount',
        'refund_amount',
        'cancellation_fee',
        'refund_percent',
        'overtime_minutes',
        'cancellation_reason',
        'cancelled_at',
        'check_in_at',
        'check_out_at',
        'needs_replacement',
        'replacement_offered_at',
        'completed_at',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'cancelled_at' => 'datetime',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'needs_replacement' => 'boolean',
        'replacement_offered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    public function label(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Nomor invoice deterministik dari kode booking: BB-20260904-7BF19 -> INV-20260904-7BF19.
     */
    public function invoiceNumber(): string
    {
        return 'INV-'.substr($this->booking_code, 3);
    }

    /**
     * Payment terbaru untuk booking ini (null jika belum ada).
     */
    public function latestPayment(): ?Payment
    {
        return $this->payments()->latest()->first();
    }

    /**
     * Estimasi total biaya: durasi dibulatkan ke atas per jam x tarif per jam.
     */
    public function estimateTotal(): float
    {
        $hours = ceil($this->start_time->diffInMinutes($this->end_time) / 60);

        return (float) $hours * (float) $this->caregiver->hourly_rate;
    }

    /**
     * Durasi layanan aktual (menit) dari check-in sampai check-out.
     * Null jika check-in atau check-out belum lengkap.
     */
    public function actualMinutes(): ?int
    {
        if (! $this->check_in_at || ! $this->check_out_at) {
            return null;
        }

        return (int) round($this->check_in_at->diffInMinutes($this->check_out_at));
    }

    /**
     * Durasi yang ditagih (menit): durasi aktual setelah check-out,
     * durasi terjadwal sebelum itu.
     */
    public function billableMinutes(): int
    {
        return $this->actualMinutes() ?? (int) $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Total yang ditagih dari durasi ditagih (pembulatan ke atas per jam).
     */
    public function billableTotal(): float
    {
        $hours = ceil($this->billableMinutes() / 60);

        return (float) $hours * (float) ($this->caregiver->hourly_rate ?? 0);
    }

    /**
     * Komisi platform (persen dari total ditagih, sesuai config/booking.php).
     */
    public function platformCommission(): float
    {
        $percent = (int) config('booking.commission.platform_percent', 0);

        return round($this->billableTotal() * $percent / 100);
    }

    /**
     * Pendapatan caregiver: total ditagih dikurangi komisi platform.
     */
    public function caregiverEarnings(): float
    {
        return $this->billableTotal() - $this->platformCommission();
    }

    /**
     * Buat kode booking unik dengan format BB-YYYYMMDD-XXXXX.
     */
    public static function generateCode(): string
    {
        do {
            $code = 'BB-'.now()->format('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        } while (static::where('booking_code', $code)->exists());

        return $code;
    }

    /**
     * Langkah alur booking utama untuk stepper di halaman detail.
     * Status selain langkah utama (mis. rejected) dianggap belum mencapai langkah manapun.
     */
    public function progressSteps(): array
    {
        $order = [
            self::STATUS_REQUESTED,
            self::STATUS_ACCEPTED,
            self::STATUS_CONFIRMED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
        ];
        $current = array_search($this->status, $order, true);

        return array_map(fn (string $status) => [
            'status' => $status,
            'label' => self::STATUS_LABELS[$status],
            'state' => match (true) {
                $current === false => 'pending',
                $status === $this->status => 'current',
                array_search($status, $order, true) < $current => 'done',
                default => 'pending',
            },
        ], $order);
    }

    /**
     * Rentang waktu [start, end] tumpang tindih dengan booking ini.
     */
    public function scopeOverlapping($query, $start, $end)
    {
        return $query->where('start_time', '<', $end)
            ->where('end_time', '>', $start);
    }
}
