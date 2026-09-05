<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Nilai awal pengaturan platform. Kontak bantuan diambil dari env (config/support.php)
 * agar sejalan dengan .env; key lain sengaja dikosongkan supaya nilai default
 * config/*.php (dan env) tetap berlaku sampai admin mengubahnya.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = Setting::definitions();

        $defaults = [
            'support.hotline' => config('support.hotline'),
            'support.whatsapp' => config('support.whatsapp'),
            'support.email' => config('support.email'),
            'support.hours' => config('support.hours'),
            'turnstile.enabled' => config('turnstile.enabled') ? '1' : '0',
            'turnstile.site_key' => config('turnstile.site_key'),
            'turnstile.secret_key' => config('turnstile.secret_key'),
        ];

        foreach ($defaults as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value],
            );
        }

        foreach ($definitions as $key => $_) {
            if (! array_key_exists($key, $defaults)) {
                Setting::firstOrCreate(['key' => $key], ['value' => null]);
            }
        }
    }
}
