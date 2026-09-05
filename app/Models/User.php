<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['public_id', 'name', 'email', 'phone', 'role', 'status', 'approved_at', 'suspended_by', 'suspended_reason', 'suspended_at', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_CAREGIVER = 'caregiver';

    public const ROLE_SUPPORT = 'support';

    public const ROLE_FINANCE = 'finance';

    public const ROLE_ADMIN = 'admin';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PENDING = 'pending';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Boot the model: auto-generate public_id saat user baru dibuat.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->public_id)) {
                $user->public_id = self::generatePublicId($user->role);
            }
        });
    }

    /**
     * Buat ID publik unik dengan format kode-role + 6 digit acak.
     */
    public static function generatePublicId(string $role): string
    {
        $prefix = match ($role) {
            self::ROLE_CAREGIVER => 'CRG',
            self::ROLE_SUPPORT, self::ROLE_FINANCE, self::ROLE_ADMIN => 'STF',
            default => 'CUS',
        };

        do {
            $publicId = $prefix.'-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('public_id', $publicId)->exists());

        return $publicId;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function caregiver(): HasOne
    {
        return $this->hasOne(Caregiver::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Jumlah notifikasi in-app yang belum dibaca (untuk badge/bell).
     */
    public function unreadNotificationsCount(): int
    {
        return $this->notifications()
            ->where('channel', Notification::CHANNEL_IN_APP)
            ->where('status', Notification::STATUS_UNREAD)
            ->count();
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function isCaregiver(): bool
    {
        return $this->role === self::ROLE_CAREGIVER;
    }

    public function isSupport(): bool
    {
        return $this->role === self::ROLE_SUPPORT;
    }

    public function isFinance(): bool
    {
        return $this->role === self::ROLE_FINANCE;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_SUPPORT, self::ROLE_FINANCE, self::ROLE_ADMIN], true);
    }

    /**
     * Apakah staf sudah disetujui admin untuk mengakses panel.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->approved_at !== null;
    }

    /**
     * Apakah staf masih menunggu persetujuan admin.
     */
    public function isPendingApproval(): bool
    {
        return $this->isStaff() && $this->status === self::STATUS_PENDING;
    }
}
