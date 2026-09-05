<x-mobile-layout title="Kebijakan Privasi">
    <!-- Sticky Top Header -->
    <div class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-slate-100 px-4 py-3.5 flex items-center gap-3">
        <a href="{{ url('/') }}" class="w-9 h-9 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors">
            <x-lucide-arrow-left class="w-5 h-5" />
        </a>
        <div>
            <h1 class="text-base font-bold text-slate-900 leading-tight">Kebijakan Privasi</h1>
            <p class="text-[11px] text-slate-500">Perlindungan data pasien & dokumen caregiver</p>
        </div>
    </div>

    <div class="p-4 space-y-5 text-sm text-slate-700 leading-relaxed">
        <!-- Privacy Shield Banner -->
        <div class="p-4 rounded-2xl bg-emerald-50/80 border border-emerald-100 flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-emerald-200">
                <x-lucide-shield-check class="w-4 h-4" />
            </div>
            <div>
                <h2 class="text-xs font-bold text-emerald-900 uppercase tracking-wider">Komitmen Keamanan Data</h2>
                <p class="text-xs text-emerald-700 mt-0.5 leading-snug">
                    BookingBoo berkomitmen menjaga kerahasiaan informasi medis keluarga Anda dan dokumen sertifikasi nakes sesuai ketentuan UU No. 27 Tahun 2022 tentang Perlindungan Data Pribadi (UU PDP).
                </p>
            </div>
        </div>

        <!-- 1. Data yang Kami Kumpulkan -->
        <section class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs space-y-2.5">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">1</span>
                <h2 class="font-bold text-slate-900 text-sm">Informasi yang Kami Kumpulkan</h2>
            </div>
            <div class="space-y-2 text-xs text-slate-600">
                <div>
                    <strong class="text-slate-800">A. Data Pasien / Keluarga (Customer):</strong>
                    <ul class="list-disc list-inside mt-0.5 text-[11px] text-slate-600">
                        <li>Nama lengkap, alamat email, dan nomor WhatsApp.</li>
                        <li>Alamat penjemputan/layanan dan catatan kebutuhan khusus pasien (misal: kursi roda, bantuan oksigen).</li>
                        <li>Nama dan nomor telepon <strong>Kontak Darurat</strong> keluarga.</li>
                    </ul>
                </div>
                <div class="pt-1">
                    <strong class="text-slate-800">B. Data Mitra Caregiver:</strong>
                    <ul class="list-disc list-inside mt-0.5 text-[11px] text-slate-600">
                        <li>KTP, Surat Tanda Registrasi (STR) / Surat Izin Perawat, Sertifikat Pelatihan, dan Surat Keterangan Sehat.</li>
                        <li>Nomor rekening bank untuk pencairan dana (*payout*).</li>
                        <li>Foto profil, tarif per jam, keahlian, dan cakupan area tugas.</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- 2. Penggunaan Informasi -->
        <section class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs space-y-2.5">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">2</span>
                <h2 class="font-bold text-slate-900 text-sm">Tujuan Penggunaan Data</h2>
            </div>
            <ul class="space-y-1.5 text-xs text-slate-600 list-disc list-inside">
                <li>Memfasilitasi reservasi jadwal pendampingan antara pasien dan caregiver.</li>
                <li>Verifikasi ketat dokumen KTP/STR oleh tim admin platform guna menjamin keamanan pasien.</li>
                <li>Penyelesaian transaksi pembayaran melalui Midtrans dan pencairan dana mitra.</li>
                <li>Menghubungi kontak darurat apabila terjadi situasi gawat darurat selama sesi pendampingan berlangsung.</li>
                <li>Penyelidikan keluhan, resolusi sengketa, dan pencatatan audit log keamanan sistem.</li>
            </ul>
        </section>

        <!-- 3. Kerahasiaan & Berbagi Data Pihak Ketiga -->
        <section class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs space-y-2.5">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">3</span>
                <h2 class="font-bold text-slate-900 text-sm">Prinsip Kerahasiaan</h2>
            </div>
            <p class="text-xs text-slate-600">
                BookingBoo <strong>tidak pernah menjual atau menyewakan</strong> data pribadi atau data kondisi medis pengguna kepada pihak manapun.
            </p>
            <p class="text-xs text-slate-600">
                Data hanya dibagikan secara terbatas untuk operasional layanan:
            </p>
            <ul class="space-y-1 text-xs text-slate-600 list-disc list-inside text-[11px]">
                <li>Alamat dan kebutuhan pasien hanya ditampilkan kepada caregiver yang menerima pesanan aktif.</li>
                <li>Data transaksi dibagikan ke payment gateway berlisensi Bank Indonesia (Midtrans) dengan enkripsi TLS.</li>
                <li>Data darurat diteruskan ke fasilitas kesehatan atau Ambulans 119 bila diminta oleh keluarga/pasien.</li>
            </ul>
        </section>

        <!-- 4. Hak Akses & Penghapusan Akun -->
        <section class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs space-y-2.5">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">4</span>
                <h2 class="font-bold text-slate-900 text-sm">Hak Pengguna & Penghapusan Data</h2>
            </div>
            <p class="text-xs text-slate-600">
                Pengguna berhak memperbarui data profil kapan saja melalui menu <strong>Profil</strong> atau meminta penghapusan akun (*account deletion*) sesuai prosedur yang berlaku, dengan syarat seluruh kewajiban transaksi telah diselesaikan.
            </p>
        </section>

        <!-- Footer Contact Link -->
        <div class="pt-2 text-center text-xs text-slate-500">
            <p>Pertanyaan mengenai privasi data dapat diajukan ke Petugas Perlindungan Data kami:</p>
            <a href="mailto:privacy@bookingboo.test" class="text-brand font-semibold hover:underline">privacy@bookingboo.test</a>
            <div class="mt-3">
                <a href="{{ route('terms') }}" class="text-xs text-slate-600 hover:text-slate-900 underline">
                    Lihat Syarat & Ketentuan Layanan &rarr;
                </a>
            </div>
        </div>
    </div>
</x-mobile-layout>
