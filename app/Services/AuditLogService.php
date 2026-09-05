<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Pencatatan audit log untuk aktivitas penting platform.
 * Penulisan dibungkus try-catch agar tidak pernah mengganggu alasan utama.
 */
class AuditLogService
{
    public function __construct(protected NotificationService $notifications) {}

    /**
     * Catat aktivitas ke audit log.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public function log(?User $actor, string $action, ?string $description = null, ?Model $subject = null, ?array $properties = null): AuditLog
    {
        try {
            return AuditLog::create([
                'user_id' => $actor?->id,
                'actor_role' => $actor?->role ?? AuditLog::ACTOR_SYSTEM,
                'action' => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'properties' => $properties,
                'ip_address' => $actor ? ($actor->last_login_ip ?? null) : null,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return new AuditLog;
        }
    }

    /**
     * Sistem mencatat aktivitas otomatis (webhook, simulasi, dsb).
     */
    public function logSystem(string $action, ?string $description = null, ?Model $subject = null, ?array $properties = null): AuditLog
    {
        try {
            return AuditLog::create([
                'user_id' => null,
                'actor_role' => AuditLog::ACTOR_SYSTEM,
                'action' => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'properties' => $properties,
                'ip_address' => null,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return new AuditLog;
        }
    }

    /**
     * IP client dari request saat ini (jika tersedia).
     */
    public function clientIp(): ?string
    {
        return request()?->ip();
    }
}
