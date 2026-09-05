<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Demo konten papan pengumuman/promo homepage.
     */
    public function run(): void
    {
        Announcement::create([
            'type' => 'promo',
            'title' => 'Promo New Member 20%',
            'message' => 'Diskon 20% untuk 3 jam layanan pertama Anda. Kode: BOOBAHRU',
            'link_url' => route('caregivers.index'),
            'link_label' => 'Coba Sekarang',
            'badge_label' => 'Promo',
            'sort_order' => 1,
        ]);

        Announcement::create([
            'type' => 'announcement',
            'title' => 'Verifikasi KTP & Sertifikat Diperketat',
            'message' => 'Mulai bulan ini semua caregiver wajib upload sertifikat keperawatan untuk tetap tampil di pencarian.',
            'link_url' => route('help'),
            'link_label' => 'Info Lengkap',
            'badge_label' => 'Pengumuman',
            'sort_order' => 2,
        ]);

        Announcement::create([
            'type' => 'ad',
            'title' => 'Butuh Pendamping Hari Ini Juga?',
            'message' => 'Caregiver terdekat dari lokasi Anda bisa langsung dipesan — cek fitur Lokasi Saya.',
            'link_url' => route('caregivers.index'),
            'link_label' => 'Cari Terdekat',
            'badge_label' => 'Iklan',
            'sort_order' => 3,
        ]);
    }
}
