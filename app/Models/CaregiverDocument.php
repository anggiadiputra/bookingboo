<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaregiverDocument extends Model
{
    protected $fillable = [
        'caregiver_id',
        'type',
        'file_path',
        'status',
        'rejection_reason',
        'reviewed_at',
    ];

    public const TYPE_KTP = 'ktp';

    public const TYPE_CERTIFICATE = 'certificate';

    public const TYPE_STR = 'str';

    public const TYPE_HEALTH_CERTIFICATE = 'health_certificate';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const TYPES = [
        self::TYPE_KTP => 'KTP',
        self::TYPE_CERTIFICATE => 'Sertifikat Pelatihan',
        self::TYPE_STR => 'STR / Surat Izin',
        self::TYPE_HEALTH_CERTIFICATE => 'Surat Keterangan Sehat',
    ];

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
