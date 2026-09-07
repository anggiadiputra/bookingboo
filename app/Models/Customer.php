<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'photo',
        'patient_name',
        'patient_birth_date',
        'patient_gender',
        'patient_blood_type',
        'patient_allergies',
        'patient_condition',
        'patient_weight_kg',
        'patient_height_cm',
        'address',
        'patient_needs',
        'emergency_contact',
        'emergency_contact_phone',
    ];

    protected function casts(): array
    {
        return [
            'patient_birth_date' => 'date',
            'patient_weight_kg' => 'float',
            'patient_height_cm' => 'float',
        ];
    }

    /**
     * Umur pasien (tahun) berdasarkan tanggal lahir, atau null bila belum diisi.
     */
    public function patientAge(): ?int
    {
        return $this->patient_birth_date?->age;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function favoriteCaregivers(): BelongsToMany
    {
        return $this->belongsToMany(Caregiver::class, 'customer_favorites')->withTimestamps();
    }
}
