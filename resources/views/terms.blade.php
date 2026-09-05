<x-mobile-layout title="Syarat & Ketentuan Layanan">
    <!-- Sticky Top Header -->
    <div class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-slate-100 px-4 py-3.5 flex items-center gap-3">
        <a href="{{ url('/') }}" class="w-9 h-9 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors">
            <x-lucide-arrow-left class="w-5 h-5" />
        </a>
        <div>
            <h1 class="text-base font-bold text-slate-900 leading-tight">Syarat & Ketentuan</h1>
            <p class="text-[11px] text-slate-500">Perjanjian penggunaan platform BookingBoo</p>
        </div>
    </div>

    <div class="p-4 space-y-5 text-sm text-slate-700 leading-relaxed">
        <!-- Hero Information Badge -->
        <div class="p-4 rounded-2xl bg-rose-50/80 border border-rose-100 flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-rose-200">
                <x-lucide-scale class="w-4 h-4" />
            </div>
            <div>
                <h2 class="text-xs font-bold text-rose-900 uppercase tracking-wider">Perjanjian Layanan</h2>
                <p class="text-xs text-rose-700 mt-0.5 leading-snug">
                    Dengan mendaftar, memesan, atau menyediakan jasa caregiver di BookingBoo, Anda menyetujui seluruh ketentuan di bawah ini. Terakhir diperbarui: 5 September 2026.
                </p>
            </div>
        </div>

        <!-- 1. Definisi & Ruang Lingkup Layanan -->
        <section class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs space-y-2.5">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">1</span>
                <h2 class="font-bold text-slate-900 text-sm">Definisi & Ruang Lingkup Layanan</h2>
            </div>
            <p class="text-xs text-slate-600">
                BookingBoo adalah platform perantara digital yang mempertemukan pengguna (pasien/keluarga pasien) dengan mitra caregiver profesional independen untuk layanan pendampingan berbayar per jam.
            </p>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 text-xs text-slate-600 space-y-1">
                <p class="font-semibold text-slate-800">Cakupan pendampingan mencakup:</p>
                <ul class="list-disc list-inside space-y-0.5 text-[11px]">
                    <li>Pendampingan kontrol rutin ke RS/klinik dan antre farmasi</li>
                    <li>Perawatan lansia (bantuan mobilisasi, pengingat minum obat, makan)</li>
                    <li>Pendampingan rawat jalan dan perawatan luka ringan pasca operasi</li>
                    <li>Latihan gerak fisik & fisioterapi ringan mandiri</li>
                </ul>
            </div>
            <p class="text-[11px] text-amber-700 bg-amber-50 p-2.5 rounded-xl border border-amber-200 font-medium">
                Penting: BookingBoo bukan fasilitas layanan kesehatan darurat dan caregiver platform tidak diperkenankan melakukan tindakan bedah, intervensi medis invasif tanpa instruksi dokter DPJP, atau pengobatan di luar wewenang profesi.
            </p>
        </section>

        <!-- 2. Pemesanan, Pembayaran & Tarif -->
        <section class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs space-y-2.5">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">2</span>
                <h2 class="font-bold text-slate-900 text-sm">Pemesanan, Tarif & Pembayaran</h2>
            </div>
            <ul class="space-y-1.5 text-xs text-slate-600 list-disc list-inside">
                <li>Durasi minimum layanan per sesi pemesanan adalah <strong>1 (satu) jam</strong>.</li>
                <li>Tarif per jam ditetapkan oleh masing-masing mitra caregiver secara transparan di profil publik.</li>
                <li>Pembayaran wajib dilakukan melalui payment gateway resmi (Midtrans) setelah caregiver menerima pesanan.</li>
                <li>Biaya layanan platform sebesar <strong>20%</strong> telah diperhitungkan secara otomatis pada sistem bagi hasil mitra.</li>
            </ul>
        </section>

        <!-- 3. Ketentuan Pembatalan & Pengembalian Dana (Refund) -->
        <section class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs space-y-2.5">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">3</span>
                <h2 class="font-bold text-slate-900 text-sm">Kebijakan Pembatalan & Refund</h2>
            </div>
            <p class="text-xs text-slate-600">
                Aturan pengembalian dana (*refund tiers*) dirancang adil bagi kedua belah pihak:
            </p>
            <div class="grid grid-cols-1 gap-2 text-xs">
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900">
                    <span>Pembatalan &ge; 48 jam sebelum jadwal</span>
                    <strong class="font-bold">Refund 100%</strong>
                </div>
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900">
                    <span>Pembatalan 24 - 48 jam sebelum jadwal</span>
                    <strong class="font-bold">Refund 50%</strong>
                </div>
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900">
                    <span>Pembatalan &lt; 24 jam sebelum jadwal</span>
                    <strong class="font-bold">Tidak ada refund (0%)</strong>
                </div>
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-sky-50 border border-sky-200 text-sky-900">
                    <span>Pembatalan sepihak oleh Caregiver</span>
                    <strong class="font-bold">Refund Penuh 100% / Caregiver Pengganti</strong>
                </div>
            </div>
        </section>

        <!-- 4. Kehadiran, Check-in & Lembur -->
        <section class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs space-y-2.5">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">4</span>
                <h2 class="font-bold text-slate-900 text-sm">Absensi Check-in & Waktu Layanan</h2>
            </div>
            <ul class="space-y-1.5 text-xs text-slate-600 list-disc list-inside">
                <li>Caregiver wajib melakukan tombol <strong>Check-in</strong> saat tiba di lokasi dan <strong>Check-out</strong> saat layanan selesai.</li>
                <li>Toleransi keterlambatan (*grace period*) check-in adalah maksimal <strong>2 jam</strong>. Jika melebihi batas waktu tanpa konfirmasi, sistem berhak membatalkan pesanan.</li>
                <li>Layanan yang melebihi jadwal yang disepakati akan dihitung berdasarkan durasi aktual sistem (*overtime billing*).</li>
            </ul>
        </section>

        <!-- 5. Komplain & Penyelesaian Sengketa -->
        <section class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs space-y-2.5">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">5</span>
                <h2 class="font-bold text-slate-900 text-sm">Komplain & Layanan Pelanggan</h2>
            </div>
            <p class="text-xs text-slate-600">
                Apabila terjadi ketidaksesuaian layanan, pengguna dapat mengajukan komplain via menu <strong>Bantuan</strong>. Tim Support & Finance platform berwenang memediasi, menahan pencairan dana mitra (*payout hold*), atau memberikan kompensasi sesuai bukti audit log.
            </p>
        </section>

        <!-- Footer Contact Link -->
        <div class="pt-2 text-center text-xs text-slate-500">
            <p>Pertanyaan mengenai syarat dan ketentuan ini dapat diajukan ke:</p>
            <a href="mailto:support@bookingboo.test" class="text-brand font-semibold hover:underline">support@bookingboo.test</a>
            <div class="mt-3">
                <a href="{{ route('privacy') }}" class="text-xs text-slate-600 hover:text-slate-900 underline">
                    Baca juga Kebijakan Privasi BookingBoo &rarr;
                </a>
            </div>
        </div>
    </div>
</x-mobile-layout>
