<?php

// Konfigurasi bantuan dan kontak darurat platform (FR-23).
// Nilai dapat diubah via environment tanpa menyentuh kode.
return [
    'hotline' => env('BOOKINGBOO_HOTLINE', '+62 811 0000 0000'),
    'whatsapp' => env('BOOKINGBOO_WHATSAPP', '+62 811 0000 0000'),
    'email' => env('BOOKINGBOO_SUPPORT_EMAIL', 'support@bookingboo.test'),
    'hours' => env('BOOKINGBOO_SUPPORT_HOURS', 'Senin-Minggu, 07.00-21.00 WIB'),
];
