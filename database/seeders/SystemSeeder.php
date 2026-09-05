<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Jejak audit dan notifikasi in-app sebagai contoh aktivitas platform.
 */
class SystemSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@bookingboo.test')->first();
        $support = User::where('email', 'support@bookingboo.test')->first();
        $finance = User::where('email', 'finance@bookingboo.test')->first();
        $sari = User::where('email', 'caregiver@bookingboo.test')->first();
        $budi = User::where('email', 'customer@bookingboo.test')->first();

        $logs = [
            [$admin, 'auth.login', User::class, $admin->id, 'Admin Platform masuk ke panel admin.'],
            [$admin, 'staff.approved', User::class, $support->id, 'Menyetujui akun staf '.$support->name.' ('.$support->public_id.').'],
            [$admin, 'staff.approved', User::class, $finance->id, 'Menyetujui akun staf '.$finance->name.' ('.$finance->public_id.').'],
            [$admin, 'caregiver.verified', null, null, 'Memverifikasi caregiver '.$sari->name.' ('.$sari->public_id.') setelah dokumen lengkap.'],
            [$support, 'booking.status_changed', null, null, 'Booking dikonfirmasi mengikuti konfirmasi pembayaran customer.'],
            [$finance, 'payout.paid', null, null, 'Mencairkan payout caregiver Rian Pratama (CRG-xxxxxx) via transfer bank.'],
            [$admin, 'review.hidden', null, null, 'Menyembunyikan review rating 2 karena memuat data pribadi pasien.'],
            [$admin, 'user.suspended', User::class, User::where('email', 'gilang@bookingboo.test')->first()?->id, 'Menonaktifkan akun staf Gilang Ramadhan (STF-xxxxxx): menghapus data komplain tanpa izin.'],
            [$budi, 'auth.login', User::class, $budi->id, $budi->name.' ('.$budi->public_id.') masuk melalui halaman utama.'],
        ];

        foreach ($logs as [$actor, $action, $subjectType, $subjectId, $description]) {
            AuditLog::create([
                'user_id' => $actor->id,
                'actor_role' => $actor->role,
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'description' => $description,
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subHours(rand(1, 72)),
            ]);
        }

        $notifications = [
            [$budi, 'booking', 'Booking Anda BB-'.now()->format('Ymd').' telah dikonfirmasi caregiver. Silakan cek detail jadwal.', 'unread'],
            [$budi, 'payment', 'Pembayaran Rp 390.000 untuk booking berlangsung telah kami terima.', 'read'],
            [$sari, 'booking', 'Booking baru masuk: pendampingan kontrol RSCM besok pukul 08.00. Mohon konfirmasi.', 'unread'],
            [$sari, 'payment', 'Payout Anda sebesar Rp 160.000 sedang diproses tim finance.', 'unread'],
            [$support, 'complaint', 'Komplain baru masuk dari customer Anisa Maharani: komunikasi kurang responsif via chat.', 'unread'],
            [$finance, 'payout', 'Pengajuan payout baru dari Sari Wulandari menunggu persetujuan Anda.', 'unread'],
            [$admin, 'system', 'Akun staf baru Dewi Lestari (support) menunggu persetujuan Anda.', 'unread'],
        ];

        foreach ($notifications as [$user, $type, $content, $status]) {
            Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'content' => $content,
                'channel' => 'in_app',
                'status' => $status,
                'sent_at' => now()->subHours(rand(1, 48)),
            ]);
        }
    }
}
