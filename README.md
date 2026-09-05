# BookingBoo — Layanan Booking Caregiver

Web app untuk pasien/keluarga pasien memesan caregiver per jam untuk menemani berobat, kontrol, dan layanan perawatan lainnya.

## Tech Stack

- **Backend**: Laravel 13 (PHP 8.3)
- **Frontend**: Laravel Blade + Tailwind CSS + Alpine.js
- **Icons**: Lucide (`mallardduck/blade-lucide-icons`)
- **Database**: SQLite (development) / MySQL-MariaDB (produksi)
- **Auth**: Laravel Breeze + verifikasi email wajib (`MustVerifyEmail`)
- **Security**: Cloudflare Turnstile pada form publik (login & registrasi)

## Persyaratan

- PHP 8.3+
- Composer 2+
- Node.js 18+ & npm

## Instalasi

```bash
# 1. Install dependensi PHP
composer install

# 2. Install dependensi JS
npm install

# 3. Salin .env dan buat APP_KEY
cp .env.example .env
php artisan key:generate

# 4. Konfigurasi database di .env
#    Development: DB_CONNECTION=sqlite (default, sudah dibuat)
#    Produksi:    DB_CONNECTION=mysql + isi DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD

# 5. Jalankan migrasi dan seeder
php artisan migrate
php artisan db:seed

# 6. Build asset
npm run build
```

## Menjalankan Server

```bash
php artisan serve
# Buka http://localhost:8000
```

Untuk development dengan hot-reload asset:

```bash
npm run dev
```

## Akun Seeder

| Role      | Email                    | Password |
|-----------|--------------------------|----------|
| Admin     | admin@bookingboo.test    | password |
| Support   | support@bookingboo.test  | password |
| Finance   | finance@bookingboo.test  | password |
| Customer  | customer@bookingboo.test  | password |
| Caregiver | caregiver@bookingboo.test | password |

## Cloudflare Turnstile

Turnstile aktif pada form login dan registrasi. Untuk development, gunakan test keys (sudah di-set default di `.env`):

- `TURNSTILE_SITE_KEY=1x00000000000000000000AA`
- `TURNSTILE_SECRET_KEY=1x0000000000000000000000000000000AA`

Untuk produksi, ganti dengan site key & secret key asli dari [Cloudflare Dashboard](https://dash.cloudflare.com/).

Untuk menonaktifkan sementara (misal saat testing), set `TURNSTILE_ENABLED=false`.

## Struktur Database

Migrasi sudah dibuat untuk entitas berikut (sesuai ERD di `docs/05-rpd.md`):

- `users` (+ kolom `phone`, `role`, `status`)
- `customers`
- `caregivers`
- `schedules`
- `bookings`
- `payments`
- `reviews`
- `complaints`
- `notifications`
- `messages`

## Role

- **customer** — Pasien / keluarga pasien
- **caregiver** — Penyedia layanan
- **support** — Staf platform: komplain & sengketa
- **finance** — Staf platform: pembayaran, refund, payout
- **admin** — Staf platform: pengguna, caregiver, tarif, moderasi

Middleware `role` digunakan untuk membatasi akses, contoh:

```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    // route khusus admin
});
```

## Dokumentasi

Dokumen analisis lengkap ada di folder [`docs/`](docs/README.md) dan diagram di [`diagrams/`](diagrams/).

## Menjalankan Test

```bash
php artisan test
```
