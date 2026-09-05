# TODO Implementasi MVP

## Analisis dan Desain

- [x] Menentukan nama produk, identitas visual, dan domain (BookingBoo, palet warna Crimson/Rose `#E11D48`, Lucide icons, mobile-first ala Halodoc, domain `bookingboo.test`).
- [x] Menetapkan tarif dasar, durasi minimum, biaya platform, dan komisi (`config/booking.php`: komisi 20%, durasi minimum 1 jam, tarif per jam mandiri).
- [x] Menetapkan aturan pembatalan, refund, keterlambatan, dan lembur (`config/booking.php`: refund tiers 48 jam = 100%, 24 jam = 50%, <24 jam = 0%, grace period 2 jam).
- [x] Menentukan dokumen verifikasi caregiver (KTP, Sertifikat Pelatihan, STR/Surat Izin, Surat Keterangan Sehat).
- [x] Menentukan format invoice dan bukti pembayaran (Format invoice `INV-YYYYMMDD-XXXX` di `Booking::invoiceNumber()`, bukti struk digital Midtrans).
- [x] Membuat wireframe mobile customer, caregiver, dan admin (`uihalodoc.md`, artefak desain visual, arsitektur dual-layout).
- [x] Meninjau kebijakan privasi, persetujuan layanan, dan syarat penggunaan (Halaman `/terms`, `/privacy`, dan persetujuan di form registrasi).

## Akun dan Profil

- [x] Membuat registrasi, login, logout, dan reset password (Laravel Breeze).
- [x] Membuat role-based access customer, caregiver, dan admin (middleware `role`).
- [x] Membuat verifikasi pendaftaran wajib (email) untuk pasien/keluarga dan staf admin/platform baru (`MustVerifyEmail`).
- [x] Mengintegrasikan Cloudflare Turnstile pada form publik (pendaftaran dan login).
- [x] Membuat peran staf admin/platform: support, finance, dan admin (model + seeder).
- [x] Membuat alur persetujuan staf admin/platform baru oleh admin sebelum akses panel.
- [x] Membuat profil customer dan kebutuhan pasien.
- [x] Membuat profil caregiver, tarif, area, keahlian, dan jadwal.
- [x] Membuat upload dan verifikasi dokumen caregiver.

## Booking

- [x] Membuat pencarian dan filter caregiver.
- [x] Membuat validasi ketersediaan dan pencegahan jadwal ganda.
- [x] Membuat pengajuan, penerimaan, dan penolakan booking.
- [x] Membuat status booking dan riwayat perubahan.
- [x] Membuat invoice dan estimasi biaya.
- [x] Membuat pembatalan customer dan caregiver.
- [x] Membuat perhitungan refund dan biaya pembatalan.
- [x] Membuat alur caregiver pengganti.

## Pembayaran dan Layanan

- [x] Memilih payment gateway.
- [x] Mengintegrasikan pembayaran dan webhook status transaksi.
- [x] Membuat check-in dan check-out.
- [x] Menghitung durasi aktual, komisi, dan pendapatan caregiver.
- [x] Membuat refund dan pencairan caregiver.
- [x] Membuat notifikasi email atau WhatsApp sesuai kemampuan MVP.

## Review, Keamanan, dan Admin

- [x] Membuat review customer yang tampil publik pada profil caregiver.
- [x] Membuat catatan pengalaman caregiver terhadap customer secara privat.
- [x] Membuat chat terkait booking dengan polling sederhana (MVP).
- [x] Menyimpan riwayat chat terkait booking untuk keperluan sengketa.
- [x] Membatasi akses chat hanya untuk pihak yang terlibat dalam booking.
- [x] Membuat komplain dan bantuan darurat.
- [x] Membuat dashboard admin untuk pengguna, booking, pembayaran, dan komplain.
- [x] Membuat moderasi review dan penonaktifan akun.
- [x] Menambahkan audit log untuk aktivitas penting.
- [x] Membuat pencairan dana caregiver (payout).
- [x] Membuat penyelesaian sengketa (dispute) oleh admin.
- [x] Membuat kontak darurat pasien.
- [x] Membuat daftar caregiver favorit customer.

## Pengujian dan Peluncuran

- [x] Menguji alur booking berhasil sampai selesai (`tests/Feature/CustomerBookingTest.php`).
- [x] Menguji pembayaran gagal, kedaluwarsa, webhook ganda, dan refund (`tests/Feature/PaymentMidtransTest.php`, `RefundPayoutTest.php`).
- [x] Menguji pembatalan sebelum dan sesudah pembayaran (`tests/Feature/CustomerBookingTest.php`, `RefundPayoutTest.php`).
- [x] Menguji pembatalan caregiver dan caregiver pengganti (`tests/Feature/CustomerBookingTest.php`).
- [x] Menguji bentrok jadwal dan check-in/check-out (`tests/Feature/AvailabilityServiceTest.php`, `CaregiverScheduleTest.php`, `CheckinCheckoutTest.php`).
- [x] Menguji batas akses review privat (`tests/Feature/ReviewTest.php`, `CaregiverNoteTest.php`).
- [x] Menguji tampilan mobile dan validasi data (`tests/Feature/CaregiverSearchTest.php`, `LegalPagesTest.php`).
- [x] Menguji keamanan akses data antar role (customer, caregiver, admin) (`tests/Feature/Auth`, `ComplaintTest.php`, `AdminDashboardTest.php`).
- [x] Menguji payment gateway pada mode sandbox (`tests/Feature/PaymentMidtransTest.php`).
- [x] Menguji alur onboarding dan verifikasi caregiver (`tests/Feature/CaregiverDocumentVerificationTest.php`).
- [x] Menyiapkan data seed untuk uji coba (`database/seeders/DatabaseSeeder.php`).
- [x] Menyiapkan deployment, backup, monitoring, dan prosedur penanganan insiden (`docs/09-deployment-dan-operasi.md`).

## Backlog (Ditunda)

- [ ] Booking berulang atau rutin (harian, mingguan, bulanan).
- [ ] Promo, kupon, dan program referral.
- [ ] Paket langganan keluarga.
- [ ] Integrasi rumah sakit, klinik, dan dokter.
- [ ] Paket korporat.
- [ ] Integrasi rekam medis.
