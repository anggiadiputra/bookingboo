<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Data contoh lengkap BookingBoo: staf, customer, caregiver,
 * marketplace (booking/pembayaran/review/komplain/payout), dan sistem.
 *
 * Jalankan: php artisan migrate:fresh --seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StaffSeeder::class,
            CustomerSeeder::class,
            CaregiverSeeder::class,
            MarketplaceSeeder::class,
            SystemSeeder::class,
            AnnouncementSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
