<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data marketplace: jadwal, booking semua status alur, pembayaran,
 * review & catatan privat, chat, komplain, payout, dan favorit.
 */
class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $budi = Customer::whereHas('user', fn ($q) => $q->where('email', 'customer@bookingboo.test'))->first();
        $anisa = Customer::whereHas('user', fn ($q) => $q->where('email', 'anisa@bookingboo.test'))->first();
        $dimas = Customer::whereHas('user', fn ($q) => $q->where('email', 'dimas@bookingboo.test'))->first();

        $sari = $this->caregiver('caregiver@bookingboo.test');
        $rian = $this->caregiver('rian@bookingboo.test');
        $nurul = $this->caregiver('nurul@bookingboo.test');

        // ── Jadwal ketersediaan (minggu berjalan) ─────────────────────────────
        $slots = [
            [$sari, 1, 8, 17], [$sari, 2, 8, 17], [$sari, 4, 8, 12],
            [$rian, 1, 9, 18], [$rian, 3, 9, 18],
            [$nurul, 2, 8, 14], [$nurul, 5, 8, 14],
        ];
        foreach ($slots as [$caregiver, $day, $start, $end]) {
            Schedule::create([
                'caregiver_id' => $caregiver->id,
                'start_time' => now()->addDays($day)->setTime($start, 0),
                'end_time' => now()->addDays($day)->setTime($end, 0),
                'status' => Schedule::STATUS_AVAILABLE,
            ]);
        }

        // ── A. Booking selesai + dibayar + review publik + catatan privat ────
        $completed = $this->booking($budi, $sari, now()->subDays(3), 8, 12, Booking::STATUS_COMPLETED, 200000, [
            'location' => 'RS Cipto Mangunkusumo, Jakarta Pusat',
            'needs' => 'Pendampingan kontrol dokter spesialis saraf dan antre obat farmasi.',
            'check_in_at' => now()->subDays(3)->setTime(7, 55),
            'check_out_at' => now()->subDays(3)->setTime(12, 5),
            'completed_at' => now()->subDays(3)->setTime(12, 5),
        ]);
        $this->payment($completed, 200000, Payment::STATUS_PAID, 'gopay', now()->subDays(4));

        Review::create([
            'booking_id' => $completed->id,
            'reviewer_id' => $budi->user_id,
            'reviewee_id' => $sari->user_id,
            'rating' => 5,
            'comment' => 'Sangat sabar dan telaten merawat ayah saya saat kontrol. Datang tepat waktu dan komunikatif!',
            'visibility' => Review::VISIBILITY_PUBLIC,
        ]);
        Review::create([
            'booking_id' => $completed->id,
            'reviewer_id' => $sari->user_id,
            'reviewee_id' => $budi->user_id,
            'rating' => 4,
            'comment' => 'Catatan privat: keluarga kooperatif, pasien kadang rewel saat minum obat, perlu pendekatan lembut.',
            'visibility' => Review::VISIBILITY_PRIVATE,
        ]);

        // ── B. Booking selesai kedua: review disembunyikan admin (moderasi) ──
        $hiddenReview = $this->booking($anisa, $rian, now()->subDays(5), 9, 12, Booking::STATUS_COMPLETED, 195000, [
            'location' => 'RS Medistra, Jakarta Selatan',
            'needs' => 'Pendampingan konsultasi bedah dan penjelasan hasil lab.',
            'check_in_at' => now()->subDays(5)->setTime(9, 0),
            'check_out_at' => now()->subDays(5)->setTime(12, 10),
            'completed_at' => now()->subDays(5)->setTime(12, 10),
        ]);
        $this->payment($hiddenReview, 195000, Payment::STATUS_PAID, 'bca_va', now()->subDays(5));

        $admin = User::where('email', 'admin@bookingboo.test')->first();
        $review = Review::create([
            'booking_id' => $hiddenReview->id,
            'reviewer_id' => $anisa->user_id,
            'reviewee_id' => $rian->user_id,
            'rating' => 2,
            'comment' => 'Kurang oke, datang agak terlambat dan tidak bawa dokumen.',
            'visibility' => Review::VISIBILITY_PUBLIC,
            'status' => Review::STATUS_HIDDEN,
            'hidden_by' => $admin->id,
            'hidden_reason' => 'Mengandung informasi dokumen pribadi pasien di kolom komentar.',
            'hidden_at' => now()->subDays(4),
        ]);

        // ── C. Booking berlangsung hari ini + chat live ──────────────────────
        $inProgress = $this->booking($budi, $rian, now(), 8, 14, Booking::STATUS_IN_PROGRESS, 390000, [
            'location' => 'RS Fatmawati, Gedung Teratai Lantai 3',
            'needs' => 'Pendampingan rawat luka pasca operasi dan mobilisasi bertahap.',
            'check_in_at' => now()->setTime(8, 2),
        ]);
        $this->payment($inProgress, 390000, Payment::STATUS_PAID, 'bca_va', now()->subHours(3));

        $this->message($inProgress, $budi->user_id, 'Selamat pagi Mas Rian, kami sudah sampai di lobi pendaftaran RS Fatmawati.', 'read', now()->subMinutes(45));
        $this->message($inProgress, $rian->user_id, 'Pagi Pak Budi, saya sudah di lobi utama memakai seragam biru tua. Saya hampiri sekarang ya.', 'read', now()->subMinutes(40));
        $this->message($inProgress, $budi->user_id, 'Baik, kami di kursi tunggu sebelah apotek. Terima kasih.', 'unread', now()->subMinutes(5));

        // ── D. Booking menunggu konfirmasi caregiver ─────────────────────────
        $requested = $this->booking($anisa, $nurul, now()->addDay(), 10, 13, Booking::STATUS_REQUESTED, 225000, [
            'location' => 'Apartemen Mediterania Tower B, Jakarta Barat',
            'needs' => 'Latihan fisioterapi pasca stroke ringan untuk kaki kanan.',
        ]);
        $this->payment($requested, 225000, Payment::STATUS_PENDING, 'midtrans', null);

        // ── E. Booking diterima caregiver, menunggu pembayaran dikonfirmasi ──
        $accepted = $this->booking($dimas, $sari, now()->addDays(2), 7, 11, Booking::STATUS_ACCEPTED, 200000, [
            'location' => 'RS Harapan Keluarga, Bekasi',
            'needs' => 'Pendampingan cuci darah sesi pertama, bawa kartu dialisis.',
        ]);
        $this->payment($accepted, 200000, Payment::STATUS_PENDING, 'midtrans', null);

        // ── F. Booking dikonfirmasi (dibayar, jalan besok) ────────────────────
        $confirmed = $this->booking($dimas, $nurul, now()->addDays(2), 13, 16, Booking::STATUS_CONFIRMED, 225000, [
            'location' => 'RS Harapan Keluarga, Bekasi',
            'needs' => 'Terapi gerak pasca cuci darah, mobilisasi ringan.',
        ]);
        $this->payment($confirmed, 225000, Payment::STATUS_PAID, 'gopay', now()->subHours(1));

        // ── G. Booking ditolak caregiver ──────────────────────────────────────
        $rejected = $this->booking($budi, $nurul, now()->addDays(3), 9, 12, Booking::STATUS_REJECTED, 225000, [
            'location' => 'Jl. Melati No. 12, Tebet, Jakarta Selatan',
            'needs' => 'Terapi jalan mandiri di rumah.',
            'cancellation_reason' => 'Maaf, jadwal saya sudah terisi untuk tanggal tersebut.',
            'cancelled_at' => now()->subHours(20),
        ]);

        // ── H. Booking dibatalkan customer dengan refund parsial ──────────────
        $cancelled = $this->booking($anisa, $sari, now()->addDays(1), 13, 17, Booking::STATUS_CANCELLED, 200000, [
            'location' => 'Klinik Medika Prima, Jakarta Pusat',
            'needs' => 'Pendampingan medical check-up rutin.',
            'refund_amount' => 120000,
            'cancellation_fee' => 80000,
            'refund_percent' => 60,
            'cancellation_reason' => 'Jadwal kontrol dokter diundur oleh pihak klinik.',
            'cancelled_at' => now()->subHours(8),
        ]);
        $payment = $this->payment($cancelled, 200000, Payment::STATUS_PAID, 'bca_va', now()->subDays(1));
        $payment->update(['status' => Payment::STATUS_REFUNDED, 'refunded_amount' => 120000]);

        // ── I. Komplain: satu baru, satu dalam review, satu selesai ───────────
        $complaintDone = $this->booking($budi, $rian, now()->subDays(8), 8, 11, Booking::STATUS_COMPLETED, 195000, [
            'location' => 'RS Siloam Semanggi, Jakarta Pusat',
            'needs' => 'Kontrol pasca operasi dengan dokter bedah.',
            'completed_at' => now()->subDays(8)->setTime(11, 5),
        ]);
        $this->payment($complaintDone, 195000, Payment::STATUS_PAID, 'gopay', now()->subDays(8));

        $support = User::where('email', 'support@bookingboo.test')->first();

        Complaint::create([
            'booking_id' => $complaintDone->id,
            'reporter_id' => $budi->user_id,
            'reason' => 'Keterlambatan kehadiran caregiver tanpa konfirmasi awal',
            'description' => 'Caregiver hadir terlambat 20 menit saat jadwal kontrol dokter sudah dimulai.',
            'status' => Complaint::STATUS_OPEN,
        ]);

        Complaint::create([
            'booking_id' => $inProgress->id,
            'reporter_id' => $anisa->user_id,
            'reason' => 'Komunikasi kurang responsif via chat',
            'description' => 'Pesan chat tidak dibalas lebih dari satu jam saat situasi mendesak.',
            'status' => Complaint::STATUS_IN_REVIEW,
            'handled_by' => $support->id,
        ]);

        Complaint::create([
            'booking_id' => $cancelled->id,
            'reporter_id' => $anisa->user_id,
            'reason' => 'Biaya pembatalan dirasa tidak sesuai',
            'description' => 'Pembatalan dilakukan sebelum caregiver menerima notifikasi, diharapkan tanpa potongan.',
            'status' => Complaint::STATUS_RESOLVED,
            'handled_by' => $support->id,
            'resolution' => 'Setelah telaah, potongan 40% dianggap wajar sesuai ketentuan 12 jam sebelum sesi. Keluarga setuju.',
        ]);

        // ── J. Payout caregiver: menunggu, sudah dibayar, ditolak ─────────────
        $finance = User::where('email', 'finance@bookingboo.test')->first();

        Payout::create([
            'caregiver_id' => $sari->id,
            'amount' => 160000,
            'status' => Payout::STATUS_PENDING,
            'note' => 'Penarikan hasil sesi kontrol RSCM (4 jam x Rp 50.000).',
        ]);
        Payout::create([
            'caregiver_id' => $rian->id,
            'amount' => 325000,
            'status' => Payout::STATUS_PAID,
            'note' => 'Pencairan minggu ke-2 bulan ini.',
            'admin_note' => 'Ditransfer ke BCA 1234567890 a.n. Rian Pratama.',
            'processed_by' => $finance->id,
            'processed_at' => now()->subDays(2),
        ]);
        Payout::create([
            'caregiver_id' => $nurul->id,
            'amount' => 50000,
            'status' => Payout::STATUS_REJECTED,
            'note' => 'Penarikan di bawah minimum Rp 100.000.',
            'admin_note' => 'Minimum pencairan Rp 100.000, silakan akumulasi terlebih dahulu.',
            'processed_by' => $finance->id,
            'processed_at' => now()->subDays(3),
        ]);

        // ── K. Favorit customer ───────────────────────────────────────────────
        $budi->favoriteCaregivers()->syncWithoutDetaching([$sari->id, $rian->id]);
        $anisa->favoriteCaregivers()->syncWithoutDetaching([$nurul->id]);
    }

    private function caregiver(string $email): Caregiver
    {
        return Caregiver::whereHas('user', fn ($q) => $q->where('email', $email))->firstOrFail();
    }

    private function booking(Customer $customer, object $caregiver, $day, int $start, int $end, string $status, float $amount, array $extra = []): Booking
    {
        return Booking::create($extra + [
            'booking_code' => Booking::generateCode(),
            'customer_id' => $customer->id,
            'caregiver_id' => $caregiver->id,
            'start_time' => $day->copy()->setTime($start, 0),
            'end_time' => $day->copy()->setTime($end, 0),
            'status' => $status,
            'total_amount' => $amount,
        ]);
    }

    private function payment(Booking $booking, float $amount, string $status, string $method, $paidAt): Payment
    {
        return Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => Payment::generateCode(),
            'amount' => $amount,
            'platform_fee' => round($amount * 0.2),
            'commission' => round($amount * 0.2),
            'method' => $method,
            'status' => $status,
            'paid_at' => $paidAt,
        ]);
    }

    private function message(Booking $booking, int $senderId, string $content, string $readStatus, $readAt): void
    {
        Message::create([
            'booking_id' => $booking->id,
            'sender_id' => $senderId,
            'content' => $content,
            'read_status' => $readStatus,
            'read_at' => $readStatus === 'read' ? $readAt : null,
        ]);
    }
}
