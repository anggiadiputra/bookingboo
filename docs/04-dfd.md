# DFD (Data Flow Diagram)

## Diagram Konteks

```mermaid
flowchart LR
    Customer["Pasien / Keluarga"] -->|"Akun, kebutuhan, booking, pembayaran, review"| Sistem(("Sistem Booking Caregiver"))
    Caregiver["Caregiver"] -->|"Akun, profil, jadwal, keputusan booking, layanan, review"| Sistem
    Support["Staf Support"] -->|"Komplain, sengketa, moderasi"| Sistem
    Finance["Staf Finance"] -->|"Pembayaran, refund, pencairan"| Sistem
    Admin["Staf Admin"] -->|"Verifikasi, persetujuan staf, tarif, komisi"| Sistem
    Sistem -->|"Profil, status booking, invoice, notifikasi, riwayat"| Customer
    Sistem -->|"Permintaan booking, jadwal, pendapatan, catatan customer"| Caregiver
    Sistem -->|"Dashboard, laporan, komplain, transaksi"| Support
    Sistem -->|"Laporan transaksi, refund, pencairan"| Finance
    Sistem -->|"Dashboard, laporan, persetujuan staf"| Admin
    Sistem <--> PG["Payment Gateway"]
```

## DFD Level 1

```mermaid
flowchart TD
    Customer["Pasien / Keluarga"]
    Caregiver["Caregiver"]
    Admin["Admin"]
    PG["Payment Gateway"]
    P1(("1. Kelola Akun dan Profil"))
    P2(("2. Cari Caregiver"))
    P3(("3. Ajukan Booking"))
    P4(("4. Kelola Pembayaran"))
    P5(("5. Kelola Pelaksanaan Layanan"))
    P6(("6. Kelola Review dan Komplain"))
    P7(("7. Kirim Notifikasi"))
    P8(("8. Kelola Chat"))
    D1[("D1 Data Pengguna")]
    D2[("D2 Profil Caregiver")]
    D3[("D3 Jadwal dan Ketersediaan")]
    D4[("D4 Data Booking")]
    D5[("D5 Data Pembayaran")]
    D6[("D6 Review dan Komplain")]
    D7[("D7 Data Chat")]

    Customer --> P1
    Caregiver --> P1
    Admin --> P1
    P1 <--> D1
    P1 <--> D2
    Customer --> P2
    P2 <--> D2
    P2 <--> D3
    P2 --> Customer
    Customer --> P3
    P3 <--> D1
    P3 <--> D3
    P3 <--> D4
    P3 --> Caregiver
    Caregiver --> P3
    Customer --> P4
    P4 <--> D4
    P4 <--> D5
    P4 <--> PG
    Customer --> P5
    Caregiver --> P5
    P5 <--> D4
    P5 --> D5
    Customer --> P6
    Caregiver --> P6
    Admin --> P6
    P6 <--> D6
    P7 <--> D4
    P7 <--> D5
    P7 --> Customer
    P7 --> Caregiver
    P7 --> Admin
    Customer --> P8
    Caregiver --> P8
    P8 <--> D4
    P8 <--> D7
```

## Data Store

| Kode | Data store | Isi utama |
|---|---|---|
| D1 | Data Pengguna | Identitas, kontak, role, status verifikasi |
| D2 | Profil Caregiver | Keahlian, tarif, area, rating |
| D3 | Jadwal dan Ketersediaan | Slot jadwal, status ketersediaan |
| D4 | Data Booking | Jadwal, durasi, lokasi, status, pembatalan |
| D5 | Data Pembayaran | Invoice, nominal, refund, komisi, pencairan |
| D6 | Review dan Komplain | Rating, komentar, laporan, moderasi |
| D7 | Data Chat | Pesan, pengirim, waktu, status baca |
