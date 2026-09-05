<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override konfigurasi dengan pengaturan dari database (jika tabel ada).
        // Nilai dari panel admin (Setting) menimpa default config/*.php.
        $this->applyDatabaseSettings();
    }

    /**
     * Terapkan pengaturan tersimpan ke config (dipakai saat boot aplikasi dan
     * bisa dipanggil ulang oleh test setelah menulis Setting ke database).
     */
    public function applyDatabaseSettings(): void
    {
        $this->applyDatabaseSettingsInternal();
    }

    /**
     * Terapkan pengaturan tersimpan ke config agar dibaca seluruh aplikasi.
     * Env (.env) tetap menjadi sumber nilai awal; baris yang tidak di-override
     * di panel admin dibiarkan memakai nilai env/config default.
     */
    private function applyDatabaseSettingsInternal(): void
    {
        if (! app()->runningInConsole() && ! Schema::hasTable('settings')) {
            return;
        }

        try {
            $settings = Setting::allKeyed();
            if ($settings === []) {
                return;
            }

            foreach (self::stringKeys() as $key => $configPath) {
                if (array_key_exists($key, $settings) && filled($settings[$key])) {
                    config([$configPath => $settings[$key]]);
                }
            }

            if (array_key_exists('midtrans.is_production', $settings)) {
                config(['midtrans.is_production' => (bool) $settings['midtrans.is_production']]);
            }

            $integerKeys = [
                'booking.commission.platform_percent',
                'booking.cancellation.default_refund_percent',
                'booking.cancellation.caregiver_refund_percent',
                'booking.checkin.grace_hours',
            ];

            foreach ($integerKeys as $key) {
                if (array_key_exists($key, $settings) && $settings[$key] !== null && $settings[$key] !== '') {
                    config([$key => (int) $settings[$key]]);
                }
            }

            // 'enabled' diturunkan dari kredensial server+client key (sama seperti config/midtrans.php).
            $serverKey = config('midtrans.server_key');
            $clientKey = config('midtrans.client_key');
            config(['midtrans.enabled' => filled($serverKey) && filled($clientKey)]);

            // Turnstile: boolean enabled (manual) + string keys.
            // Aktif HANYA jika toggle menyala DAN memakai key asli (bukan test key
            // 1x0000...), sehingga widget tidak muncul dengan key dummy.
            $turnstileEnabled = (bool) ($settings['turnstile.enabled'] ?? config('turnstile.enabled', true));
            $turnstileSite = $settings['turnstile.site_key'] ?? config('turnstile.site_key', '');
            $turnstileSecret = $settings['turnstile.secret_key'] ?? config('turnstile.secret_key', '');

            $turnstileSite = filled($turnstileSite) && ! str_starts_with((string) $turnstileSite, '1x0000') ? (string) $turnstileSite : '';
            $turnstileSecret = filled($turnstileSecret) && ! str_starts_with((string) $turnstileSecret, '1x0000') ? (string) $turnstileSecret : '';

            config(['turnstile.site_key' => $turnstileSite]);
            config(['turnstile.secret_key' => $turnstileSecret]);
            config(['turnstile.enabled' => $turnstileEnabled && $turnstileSite !== '' && $turnstileSecret !== '']);

            // SMTP: aktifkan mailer smtp bila host terisi; jika tidak, biarkan
            // nilai .env (mis. log) berlaku.
            $mailHost = $settings['mail.host'] ?? '';
            if (filled($mailHost)) {
                config([
                    'mail.default' => $settings['mail.mailer'] ?? 'smtp',
                    'mail.mailers.smtp.host' => $mailHost,
                    'mail.mailers.smtp.port' => (int) ($settings['mail.port'] ?? 587),
                    'mail.mailers.smtp.username' => $settings['mail.username'] ?? null,
                    'mail.mailers.smtp.password' => $settings['mail.password'] ?? null,
                    'mail.mailers.smtp.encryption' => $settings['mail.encryption'] ?? 'tls',
                ]);
            }
            if (filled($settings['mail.from_address'] ?? '')) {
                config(['mail.from.address' => $settings['mail.from_address']]);
                config(['mail.from.name' => $settings['mail.from_name'] ?? config('app.name', 'BookingBoo')]);
            }
        } catch (\Throwable) {
            // Tabel settings belum siap (mis. saat migrasi pertama) — abaikan.
        }
    }

    /**
     * Key string (kontak & kredensial payment) yang di-override ke config.
     *
     * @return array<string, string> key setting => path config
     */
    private static function stringKeys(): array
    {
        return [
            'support.hotline' => 'support.hotline',
            'support.whatsapp' => 'support.whatsapp',
            'support.email' => 'support.email',
            'support.hours' => 'support.hours',
            'midtrans.server_key' => 'midtrans.server_key',
            'midtrans.client_key' => 'midtrans.client_key',
        ];
    }
}
