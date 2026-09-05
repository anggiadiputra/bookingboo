<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun staf platform: admin, support, finance.
 * Termasuk contoh staf pending approval dan staf nonaktif untuk demo moderasi.
 */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Platform',
            'email' => 'admin@bookingboo.test',
            'phone' => '081110000001',
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        User::create([
            'name' => 'Rina Kartika',
            'email' => 'support@bookingboo.test',
            'phone' => '081110000002',
            'role' => User::ROLE_SUPPORT,
            'status' => User::STATUS_ACTIVE,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        User::create([
            'name' => 'Fajar Nugroho',
            'email' => 'finance@bookingboo.test',
            'phone' => '081110000003',
            'role' => User::ROLE_FINANCE,
            'status' => User::STATUS_ACTIVE,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        // Staf baru menunggu persetujuan admin (muncul di panel persetujuan staf)
        User::create([
            'name' => 'Dewi Lestari',
            'email' => 'dewi@bookingboo.test',
            'phone' => '081110000004',
            'role' => User::ROLE_SUPPORT,
            'status' => User::STATUS_PENDING,
            'approved_at' => null,
            'password' => 'password',
        ]);

        // Staf nonaktif: contoh hasil moderasi (muncul di panel audit log & moderasi)
        User::create([
            'name' => 'Gilang Ramadhan',
            'email' => 'gilang@bookingboo.test',
            'phone' => '081110000005',
            'role' => User::ROLE_SUPPORT,
            'status' => User::STATUS_SUSPENDED,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'suspended_by' => 1, // Admin Platform
            'suspended_reason' => 'Menghapus data komplain tanpa izin (demo akun nonaktif).',
            'suspended_at' => now()->subDays(6),
            'password' => 'password',
        ]);
    }
}
