<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan platform (key-value) yang diedit lewat panel admin.
 * Nilai dipakai dengan meng-override config saat boot (AppServiceProvider),
 * sehingga kode lain cukup membaca config('support.*'), config('midtrans.*'),
 * config('booking.*') seperti biasa.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Daftar key yang dikenal beserta tipenya untuk validasi & seeding.
     *
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        return [
            // Kontak & bantuan (FR-23) — dibaca halaman /help via config('support.*').
            'support.hotline' => 'string',
            'support.whatsapp' => 'string',
            'support.email' => 'string',
            'support.hours' => 'string',

            // Payment gateway Midtrans — dipakai MidtransService.
            'midtrans.server_key' => 'string',
            'midtrans.client_key' => 'string',
            'midtrans.is_production' => 'boolean',

            // Komisi platform & kebijakan pembatalan/refund.
            'booking.commission.platform_percent' => 'integer',
            'booking.cancellation.default_refund_percent' => 'integer',
            'booking.cancellation.caregiver_refund_percent' => 'integer',
            'booking.checkin.grace_hours' => 'integer',

            // Cloudflare Turnstile (FR-30).
            'turnstile.enabled' => 'boolean',
            'turnstile.site_key' => 'string',
            'turnstile.secret_key' => 'string',

            // SMTP / mail (pengiriman verifikasi, reset password, notifikasi).
            'mail.mailer' => 'string',
            'mail.host' => 'string',
            'mail.port' => 'integer',
            'mail.username' => 'string',
            'mail.password' => 'string',
            'mail.encryption' => 'string',
            'mail.from_address' => 'string',
            'mail.from_name' => 'string',
        ];
    }

    /**
     * Ambil semua setting dalam bentuk [key => value mentah (string|null)].
     *
     * @return array<string, string|null>
     */
    public static function allKeyed(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }
}
