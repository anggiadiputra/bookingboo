<?php

// Kebijakan pembatalan dan refund MVP.
// Persentase refund customer mengikuti batas waktu pembatalan (jam sebelum start_time),
// diurutkan dari ambang terbesar. Angka dapat disesuaikan admin tanpa ubah kode.
return [
    'cancellation' => [
        // Ambang jam sebelum jadwal -> persen refund dari total biaya
        'customer_refund_tiers' => [
            ['hours' => 48, 'percent' => 100],
            ['hours' => 24, 'percent' => 50],
        ],
        // Pembatalan kurang dari 24 jam sebelum jadwal: tanpa refund
        'default_refund_percent' => 0,

        // Pembatalan oleh caregiver dianggap kesalahan caregiver: refund penuh
        'caregiver_refund_percent' => 100,
    ],

    // Check-in / check-out layanan oleh caregiver.
    'checkin' => [
        // Batas waktu check-in setelah end_time (jam) sebelum perlu bantuan admin.
        'grace_hours' => 2,
    ],

    // Komisi platform dari total layanan (persen).
    'commission' => [
        'platform_percent' => 20,
    ],
];
