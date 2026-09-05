<x-app-layout>
    <!-- Halodoc Mobile Header -->
    <div class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-slate-100 px-4 py-3.5 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ url('/dashboard') }}" class="w-9 h-9 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors">
                <x-lucide-arrow-left class="w-5 h-5" />
            </a>
            <div>
                <h1 class="text-base font-bold text-slate-900 leading-tight">Bantuan & Darurat 24/7</h1>
                <p class="text-[11px] text-slate-500">Layanan pendampingan & call center platform</p>
            </div>
        </div>
    </div>

    <div class="px-4 py-4 space-y-4">
        <!-- Halodoc Emergency Red SOS Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-rose-600 to-rose-700 p-4 text-white shadow-xl shadow-rose-200">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center shrink-0 backdrop-blur">
                    <x-lucide-siren class="w-5 h-5 text-white animate-pulse" />
                </div>
                <div>
                    <h2 class="text-sm font-extrabold leading-snug">Darurat Medis Kritis?</h2>
                    <p class="text-xs text-rose-100 mt-1 leading-relaxed">
                        Jika pasien mengalami kondisi gawat darurat yang mengancam nyawa, segera hubungi layanan ambulans gawat darurat nasional terlebih dahulu.
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <a href="tel:119" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white text-rose-600 text-xs font-bold shadow-sm hover:bg-rose-50 transition-colors">
                            <x-lucide-phone-call class="w-3.5 h-3.5" />
                            <span>Panggil Ambulans 119</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanal Kontak Platform (One-Tap Action Grid) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                Kontak Darurat Platform
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <!-- Hotline Telepon -->
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', config('support.hotline')) }}" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-rose-50 hover:border-rose-200 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 group-hover:bg-rose-500 group-hover:text-white transition-colors">
                        <x-lucide-phone class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <span class="text-[10px] text-slate-400 font-semibold uppercase block">Telepon Siaga</span>
                        <p class="text-xs font-bold text-slate-900 group-hover:text-rose-600 transition-colors">{{ config('support.hotline') }}</p>
                    </div>
                </a>

                <!-- WhatsApp -->
                @php $waClean = preg_replace('/[^0-9]/', '', config('support.whatsapp')); @endphp
                <a href="https://wa.me/{{ $waClean }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-emerald-50 hover:border-emerald-200 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                        <x-lucide-message-circle class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <span class="text-[10px] text-slate-400 font-semibold uppercase block">WhatsApp Official</span>
                        <p class="text-xs font-bold text-slate-900 group-hover:text-emerald-600 transition-colors">{{ config('support.whatsapp') }}</p>
                    </div>
                </a>

                <!-- Email -->
                <a href="mailto:{{ config('support.email') }}" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-sky-50 hover:border-sky-200 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center shrink-0 group-hover:bg-sky-500 group-hover:text-white transition-colors">
                        <x-lucide-mail class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <span class="text-[10px] text-slate-400 font-semibold uppercase block">Email Dukungan</span>
                        <p class="text-xs font-bold text-slate-900 truncate group-hover:text-sky-600 transition-colors">{{ config('support.email') }}</p>
                    </div>
                </a>

                <!-- Jam Operasional -->
                <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/50">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                        <x-lucide-clock class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <span class="text-[10px] text-slate-400 font-semibold uppercase block">Jam Operasional</span>
                        <p class="text-xs font-bold text-slate-900">{{ config('support.hours') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Komplain Aktif Milik Pengguna -->
        @if($openComplaints && $openComplaints->isNotEmpty())
            <div class="bg-white rounded-2xl border border-amber-200 p-4 shadow-sm space-y-3 bg-amber-50/30">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold text-amber-900 flex items-center gap-1.5">
                        <x-lucide-alert-triangle class="w-4 h-4 text-amber-600" />
                        <span>Komplain Aktif Anda</span>
                    </h2>
                    <span class="text-[11px] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">
                        {{ $openComplaints->count() }} kasus
                    </span>
                </div>

                <div class="space-y-2">
                    @foreach($openComplaints as $complaint)
                        <div class="p-3 bg-white rounded-xl border border-amber-200/80 shadow-xs flex items-center justify-between gap-2">
                            <div class="text-xs">
                                <span class="font-bold text-slate-900">{{ $complaint->booking->booking_code }}</span>
                                <p class="text-slate-500 text-[11px] mt-0.5">{{ $complaint->reason }}</p>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold {{ $complaint->status === 'open' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $complaint->status === 'open' ? 'Baru' : 'Ditinjau' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Pusat Informasi & FAQ Sering Ditanyakan -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                Pertanyaan yang Sering Diajukan (FAQ)
            </h2>

            <div class="space-y-2 text-xs">
                <details class="group p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <summary class="cursor-pointer font-bold text-slate-800 flex items-center justify-between">
                        <span>Bagaimana cara mengajukan komplain?</span>
                        <x-lucide-chevron-down class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform" />
                    </summary>
                    <p class="mt-2 text-slate-600 leading-relaxed text-[11px]">
                        Buka menu "Booking Saya", pilih reservasi terkait, lalu buka bagian "Bantuan & Kendala Layanan" untuk mengisi uraian komplain. Tim support kami akan merespons dalam 1x24 jam.
                    </p>
                </details>

                <details class="group p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <summary class="cursor-pointer font-bold text-slate-800 flex items-center justify-between">
                        <span>Bagaimana jika caregiver tidak hadir di jadwal?</span>
                        <x-lucide-chevron-down class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform" />
                    </summary>
                    <p class="mt-2 text-slate-600 leading-relaxed text-[11px]">
                        Sistem kami otomatis mendeteksi ketiadaan absensi. Anda berhak mendapatkan opsi penjadwalan ulang dengan caregiver pengganti terverifikasi atau pengembalian dana (refund) 100%.
                    </p>
                </details>

                <details class="group p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <summary class="cursor-pointer font-bold text-slate-800 flex items-center justify-between">
                        <span>Apakah caregiver sudah terverifikasi STR medis?</span>
                        <x-lucide-chevron-down class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform" />
                    </summary>
                    <p class="mt-2 text-slate-600 leading-relaxed text-[11px]">
                        Semua mitra caregiver di platform BookingBoo yang bertanda centang hijau telah melewati verifikasi ketat Surat Tanda Registrasi (STR) perawat/bidan, identitas resmi KTP, dan sertifikasi keahlian.
                    </p>
                </details>
            </div>
        </div>
    </div>
</x-app-layout>