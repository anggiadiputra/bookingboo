<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caregiver extends Model
{
    protected $fillable = [
        'user_id',
        'skills',
        'service_area',
        'latitude',
        'longitude',
        'hourly_rate',
        'rating',
        'verification_status',
        'bio',
        'photo',
    ];

    /**
     * Jarak Haversine (km) antara caregiver dan titik lat/lng yang diberikan.
     */
    public function distanceFromKm(float $lat, float $lng): float
    {
        $lat1 = deg2rad((float) $this->latitude);
        $lat2 = deg2rad((float) $lat);
        $dLat = $lat2 - $lat1;
        $dLng = deg2rad((float) $lng) - deg2rad((float) $this->longitude);

        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;
        $c = 2 * asin(min(1.0, sqrt($a)));

        return 6371.0 * $c;
    }

    /**
     * Route binding memakai public_id (CRG-XXXXXX) yang tersimpan di tabel
     * users, agar ID internal tidak terekspos di URL publik.
     */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function getPublicIdAttribute(): string
    {
        return $this->user->public_id;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->whereHas('user', fn ($q) => $q->where('public_id', $value))->first();
    }

    public const VERIFICATION_PENDING = 'pending';

    public const VERIFICATION_VERIFIED = 'verified';

    public const VERIFICATION_REJECTED = 'rejected';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function favoritedByCustomers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_favorites')->withTimestamps();
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CaregiverDocument::class);
    }

    /**
     * Review publik yang ditujukan ke user caregiver ini
     * (tabel reviews memakai reviewer_id/reviewee_id ke users).
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id', 'user_id')
            ->where('visibility', Review::VISIBILITY_PUBLIC);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    /**
     * Sinkronkan verification_status berdasarkan status dokumen:
     * semua disetujui -> verified, ada yang ditolak -> rejected, selain itu -> pending.
     */
    public function syncVerificationStatus(): void
    {
        $documents = $this->load('documents')->documents;

        if ($documents->isEmpty()) {
            $this->update(['verification_status' => self::VERIFICATION_PENDING]);

            return;
        }

        if ($documents->contains(fn ($d) => $d->status === CaregiverDocument::STATUS_REJECTED)) {
            $this->update(['verification_status' => self::VERIFICATION_REJECTED]);

            return;
        }

        if ($documents->every(fn ($d) => $d->status === CaregiverDocument::STATUS_APPROVED)) {
            $this->update(['verification_status' => self::VERIFICATION_VERIFIED]);

            return;
        }

        $this->update(['verification_status' => self::VERIFICATION_PENDING]);
    }
}
