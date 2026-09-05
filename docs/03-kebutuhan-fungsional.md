# Kebutuhan Fungsional

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-01 | Registrasi dan login customer serta caregiver | Must |
| FR-02 | Profil dan role customer, caregiver, dan admin | Must |
| FR-03 | Pengiriman dan verifikasi identitas caregiver | Must |
| FR-04 | Persetujuan, penolakan, dan penonaktifan caregiver oleh admin | Must |
| FR-05 | Pengaturan tarif, area, keahlian, dan ketersediaan caregiver | Must |
| FR-06 | Pencarian dan filter caregiver | Must |
| FR-07 | Tampilan profil dan jadwal caregiver | Must |
| FR-08 | Booking berdasarkan tanggal, jam, durasi, lokasi, dan kebutuhan | Must |
| FR-09 | Penerimaan atau penolakan permintaan booking | Must |
| FR-10 | Pencegahan bentrok jadwal | Must |
| FR-11 | Invoice dan estimasi biaya | Must |
| FR-12 | Integrasi payment gateway dan status pembayaran | Must |
| FR-13 | Notifikasi status booking dan pembayaran | Must |
| FR-14 | Chat customer dan caregiver (polling) | Must |
| FR-15 | Check-in dan check-out caregiver | Must |
| FR-16 | Perhitungan durasi aktual, biaya, dan komisi | Must |
| FR-17 | Pembatalan customer dan caregiver | Must |
| FR-18 | Perhitungan refund dan biaya pembatalan | Must |
| FR-19 | Review customer terhadap caregiver yang tampil publik | Must |
| FR-20 | Review caregiver terhadap customer yang bersifat privat | Must |
| FR-21 | Moderasi review dan komplain oleh admin | Must |
| FR-22 | Riwayat booking dan transaksi | Must |
| FR-23 | Bantuan atau laporan keadaan darurat | Must |
| FR-24 | Dashboard laporan transaksi dan performa caregiver | Should |
| FR-25 | Pencairan dana caregiver (payout) | Must |
| FR-26 | Penyelesaian sengketa (dispute) oleh admin | Should |
| FR-27 | Kontak darurat pasien | Should |
| FR-28 | Daftar caregiver favorit customer | Should |
| FR-29 | Verifikasi pendaftaran wajib untuk pasien/keluarga dan staf admin/platform baru | Must |
| FR-30 | Proteksi form publik dengan Cloudflare Turnstile | Must |
| FR-31 | Peran staf admin/platform: support, finance, dan admin | Must |
| FR-32 | Persetujuan staf admin/platform baru oleh admin sebelum akses panel | Must |

## Kebutuhan Nonfungsional

- **Keamanan:** password di-hash, akses berbasis role, dan data sensitif dibatasi.
- **Privasi:** data medis dan kontak hanya terlihat pihak berkepentingan.
- **Ketersediaan:** status booking dan pembayaran harus konsisten.
- **Auditabilitas:** perubahan booking, pembayaran, refund, dan moderasi dicatat.
- **Usability:** alur booking jelas dan nyaman di perangkat mobile.
- **Keandalan:** webhook pembayaran idempotent agar tidak diproses ganda.
- **Performa:** pencarian caregiver merespons dalam waktu yang wajar.
- **Kepatuhan:** pengelolaan data pribadi mengikuti peraturan perlindungan data (misal UU PDP Indonesia).
- **Aksesibilitas:** antarmuka dapat digunakan oleh pengguna dengan keterbatasan.
- **Retensi data:** kebijakan penyimpanan dan penghapusan data pribadi yang jelas.

## Backlog (Ditunda)

Fitur berikut tidak masuk MVP tetapi dicatat agar tidak hilang:

- Booking berulang atau rutin (harian, mingguan, bulanan).
- Promo, kupon, dan program referral.
- Paket langganan keluarga.
- Integrasi rumah sakit, klinik, dan dokter.
- Paket korporat.
- Integrasi rekam medis.
