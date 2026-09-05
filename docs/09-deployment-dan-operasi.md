# Panduan Deployment, Backup, Monitoring & Prosedur Penanganan Insiden

Dokumen ini memuat standar operasional prosedur (SOP) untuk deployment produksi, pemeliharaan rutin (*backup & monitoring*), serta penanganan insiden darurat pada platform **BookingBoo**.

---

## 1. Arsitektur & Spesifikasi Lingkungan Produksi

### 1.1 Kebutuhan Server (VPS / Cloud)
- **Sistem Operasi:** Ubuntu 22.04 LTS / 24.04 LTS (atau server setara).
- **Web Server:** Nginx (disarankan) atau Apache 2.4 dengan mod_rewrite.
- **PHP Runtime:** PHP 8.3 dengan ekstensi: `php8.3-fpm`, `php8.3-sqlite3` (atau `php8.3-mysql`), `php8.3-mbstring`, `php8.3-xml`, `php8.3-curl`, `php8.3-zip`, `php8.3-bcmath`, `php8.3-intl`.
- **Database Engine:** MySQL 8.0 / MariaDB 10.11 (atau SQLite terisolasi untuk skala awal).
- **Process Manager:** Systemd / Supervisor untuk queue worker.
- **SSL Certificate:** Let's Encrypt Certbot (HTTPS wajib untuk webhook Midtrans & proteksi data privasi).

### 1.2 Konfigurasi Virtual Host Nginx Contoh
```nginx
server {
    listen 80;
    server_name bookingboo.id www.bookingboo.id;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name bookingboo.id www.bookingboo.id;
    root /var/www/bookingboo/public;

    ssl_certificate /etc/letsencrypt/live/bookingboo.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/bookingboo.id/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 2. Alur Deployment Aplikasi

### 2.1 Checklist Langkah Rilis (Zero-Downtime Pipeline)
1. **Pull Kode Terbaru:**
   ```bash
   git pull origin main
   ```
2. **Instalasi Dependensi PHP:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. **Kompilasi Asset Frontend:**
   ```bash
   npm ci
   npm run build
   ```
4. **Migrasi Database:**
   ```bash
   php artisan migrate --force
   ```
5. **Optimasi Cache Konfigurasi & Route:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
6. **Hubungkan Storage Publik:**
   ```bash
   php artisan storage:link
   ```
7. **Restart Queue Worker:**
   ```bash
   php artisan queue:restart
   ```

### 2.2 Konfigurasi Background Worker (Supervisor)
Simpan di `/etc/supervisor/conf.d/bookingboo-worker.conf`:
```ini
[program:bookingboo-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/bookingboo/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/bookingboo/storage/logs/worker.log
stopwaitsecs=3600
```

### 2.3 Konfigurasi Cron Scheduler
Jalankan `crontab -e -u www-data`:
```cron
* * * * * cd /var/www/bookingboo && php artisan schedule:run >> /dev/null 2>&1
```

---

## 3. Strategi Pencadangan (Backup Strategy)

### 3.1 Komponen yang Wajib Dicadangkan
1. **Database:** File database SQLite (`database/database.sqlite`) atau dump MySQL (`mysqldump`).
2. **File Storage Pengguna:** Dokumen KTP, STR nakes, dan sertifikat pelatihan di `storage/app/public/documents/`.
3. **Konfigurasi Lingkungan:** Salinan berkas `.env` yang tersimpan di vault aman terenkripsi (bukan di git publik).

### 3.2 Jadwal & Retensi Otomatis
- **Frekuensi:** Harian (*Daily*) setiap pukul 02.00 dini hari (WIB).
- **Retensi:**
  - Snapshot harian disimpan selama 14 hari.
  - Snapshot mingguan disimpan selama 8 minggu.
  - Snapshot bulanan disimpan selama 6 bulan.
- **Penyimpanan Luar (*Offsite Storage*):** Dicadangkan ke S3 / Cloud Storage terpisah dengan enkripsi *server-side*.

### 3.3 Skrip Otomatisasi Backup Harian Contoh (`backup.sh`)
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/bookingboo"
mkdir -p "$BACKUP_DIR"

# 1. Backup SQLite Database
sqlite3 /var/www/bookingboo/database/database.sqlite ".backup '$BACKUP_DIR/db_$DATE.sqlite'"

# 2. Backup Uploaded Caregiver Documents
tar -czf "$BACKUP_DIR/storage_$DATE.tar.gz" -C /var/www/bookingboo/storage/app/public documents

# 3. Hapus backup yang lebih dari 14 hari
find "$BACKUP_DIR" -type f -mtime +14 -delete
```

### 3.4 Uji Pemulihan Bencana (*Disaster Recovery Drill*)
- Setiap 3 bulan sekali, tim teknis wajib melakukan simulasi *restore* database dan storage pada lingkungan staging untuk memverifikasi integritas data backup.

---

## 4. Pemantauan Sistem (Monitoring & Health Checks)

### 4.1 Metrik Pemantauan Utama
- **Ketersediaan Server (Uptime):** Uptime monitoring via Uptime Kuma / Pingdom / Better Uptime pada endpoint `/` dan `/terms`.
- **Log Error Aplikasi:** Menggunakan Laravel logging (`storage/logs/laravel.log`) dan alat pemantau exception (Sentry / Flare).
- **Monitoring Webhook Payment Gateway:** Pemantauan status transaksi pending > 30 menit tanpa webhook konfirmasi.
- **Penggunaan Sumber Daya:** Utilisasi CPU, memori RAM, dan kapasitas disk penyimpanan dokumen (>80% memicu notifikasi peringatan).

---

## 5. Standar Operasional Prosedur (SOP) Penanganan Insiden

### 5.1 Insiden Pembayaran (Payment Gateway Mismatch / Webhook Timeout)
1. **Gejala:** Customer telah mentransfer dana, namun status booking masih tertahan di `accepted` (belum `confirmed`).
2. **Tindakan Staf Finance:**
   - Cek dashboard Midtrans Sandbox/Production menggunakan nomor `payment_code` / `booking_code`.
   - Jika status di Midtrans adalah `settlement`, lakukan resend webhook atau update manual melalui tombol *Rekonsiliasi Pembayaran* di panel staf.
   - Periksa audit log untuk memastikan tanda tangan enkripsi `signature_key` valid.

### 5.2 Insiden Caregiver Terlambat / Tidak Hadir (*No-Show*)
1. **Gejala:** Caregiver belum melakukan tombol **Check-in** setelah batas toleransi 2 jam (*grace period*) dari `start_time`.
2. **Tindakan Sistem:**
   - Sistem secara otomatis menandai status pesanan dan memunculkan peringatan darurat pada panel staf support.
3. **Tindakan Staf Support:**
   - Hubungi caregiver via telepon / WhatsApp resmi.
   - Hubungi customer untuk mengonfirmasi kondisi pasien.
   - Tawarkan opsi:
     - **Assignment Caregiver Pengganti:** Pilih caregiver terdekat yang berstatus *available* melalui menu `/admin/bookings/pengganti`.
     - **Pembatalan & Refund Penuh (100%):** Memproses pengembalian dana penuh seketika tanpa potongan biaya platform.

### 5.3 Insiden Kegawatdaruratan Medis Saat Pendampingan
1. **Gejala:** Pasien mengalami perburukan kondisi kesehatan mendadak, henti nafas, atau kecelakaan saat sesi layanan.
2. **Protokol Darurat Caregiver:**
   - Segera hubungi **Hotline Gawat Darurat Ambulans Nasional 119**.
   - Hubungi nomor **Kontak Darurat Keluarga Pasien** yang tertera di aplikasi.
   - Bawa pasien ke IGD rumah sakit terdekat.
   - Hubungi hotline darurat platform BookingBoo (tersedia 24/7 di aplikasi).

### 5.4 Insiden Sengketa Layanan & Pengajuan Komplain
1. **Alur Eskalasi:**
   - Customer membuat tiket komplain melalui menu **Bantuan & Darurat**.
   - Sistem secara otomatis membekukan pencairan dana (*payout hold*) untuk caregiver yang bersangkutan.
   - Staf Support mengulas catatan riwayat chat, waktu aktual absensi check-in/out, dan bukti komplain.
   - Staf Finance mengeksekusi resolusi: penolakan komplain (jika tidak terbukti) atau pengembalian dana (*refund customer*) disertai penalti saldo mitra.
   - Seluruh tindakan tercatat secara permanen di tabel `audit_logs`.
