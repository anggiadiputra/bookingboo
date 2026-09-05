<x-mobile-layout title="Layanan Pendampingan Caregiver & Medis">
    <!-- Top Bar with Location, Brand & Search -->
    <x-mobile-header :showSearch="true" />

    <div class="px-4 py-4 space-y-6">
        <!-- Papan Pengumuman / Promo / Iklan (slider) -->
        <x-promo-board :announcements="$announcements" />

        <!-- 4 Grid Kategori Layanan Cepat (Ala Halodoc) -->
        <section>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 px-1">Layanan Utama</h2>
            <div class="grid grid-cols-4 gap-2.5 sm:gap-4 text-center">
                <!-- 1. Pendamping RS -->
                <a href="{{ route('caregivers.index', ['q' => 'Rumah Sakit']) }}" class="group flex flex-col items-center p-2 rounded-2xl hover:bg-slate-50 transition-all">
                    <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                        <x-lucide-hospital class="w-6 h-6 stroke-[2.2]" />
                    </div>
                    <span class="text-xs font-semibold text-slate-800 mt-2 leading-tight">Pendamping<br>RS</span>
                </a>

                <!-- 2. Rawat Lansia -->
                <a href="{{ route('caregivers.index', ['q' => 'Lansia']) }}" class="group flex flex-col items-center p-2 rounded-2xl hover:bg-slate-50 transition-all">
                    <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                        <x-lucide-heart-handshake class="w-6 h-6 stroke-[2.2]" />
                    </div>
                    <span class="text-xs font-semibold text-slate-800 mt-2 leading-tight">Perawatan<br>Lansia</span>
                </a>

                <!-- 3. Pasca Operasi -->
                <a href="{{ route('caregivers.index', ['q' => 'Operasi']) }}" class="group flex flex-col items-center p-2 rounded-2xl hover:bg-slate-50 transition-all">
                    <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                        <x-lucide-stethoscope class="w-6 h-6 stroke-[2.2]" />
                    </div>
                    <span class="text-xs font-semibold text-slate-800 mt-2 leading-tight">Pasca<br>Operasi</span>
                </a>

                <!-- 4. Fisioterapi -->
                <a href="{{ route('caregivers.index', ['q' => 'Terapi']) }}" class="group flex flex-col items-center p-2 rounded-2xl hover:bg-slate-50 transition-all">
                    <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                        <x-lucide-activity class="w-6 h-6 stroke-[2.2]" />
                    </div>
                    <span class="text-xs font-semibold text-slate-800 mt-2 leading-tight">Terapi &<br>Gerak</span>
                </a>
            </div>
        </section>

        <!-- Emergency Care & 24/7 Hotline Banner -->
        <section>
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-rose-600 to-rose-700 p-4 sm:p-5 text-white shadow-md flex items-center min-h-40">
                <div class="relative z-10 max-w-[70%]">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-white/20 text-white text-[10px] font-bold uppercase tracking-wider mb-1.5">
                        <x-lucide-shield-alert class="w-3 h-3" />
                        Siaga 24 Jam
                    </span>
                    <h3 class="text-sm sm:text-base font-bold leading-snug">Pendampingan Darurat Medis & Pasien Kritis</h3>
                    <p class="text-xs text-rose-100 mt-1">Butuh bantuan darurat kontrol ke RS hari ini?</p>
                    <div class="mt-3 flex items-center gap-2">
                        <a href="{{ route('help') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white text-rose-600 text-xs font-bold hover:bg-rose-50 shadow-xs transition-transform active:scale-95">
                            <x-lucide-phone class="w-3.5 h-3.5" />
                            Kontak Darurat
                        </a>
                    </div>
                </div>
                
                <!-- Floating Decorative Stethoscope / Cross Watermark -->
                <div class="absolute -right-3 -bottom-4 opacity-20 pointer-events-none">
                    <x-lucide-heart-pulse class="w-32 h-32 text-white" />
                </div>
            </div>
        </section>

        <!-- Caregiver Terverifikasi Pilihan -->
        <section>
            <div class="flex items-center justify-between mb-3 px-1">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Caregiver Terverifikasi</h2>
                    <p class="text-[11px] text-slate-500">Penyedia jasa terbaik berizin resmi & lulus verifikasi</p>
                </div>
                <a href="{{ route('caregivers.index') }}" class="text-xs font-semibold text-brand hover:underline flex items-center gap-0.5">
                    Lihat Semua
                    <x-lucide-chevron-right class="w-3.5 h-3.5" />
                </a>
            </div>

            @if($caregivers->isEmpty())
                <div class="p-8 text-center bg-slate-50 rounded-2xl border border-slate-200/80">
                    <x-lucide-user-x class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                    <p class="text-sm font-medium text-slate-700">Belum ada caregiver terverifikasi</p>
                    <p class="text-xs text-slate-500 mt-1">Data caregiver sedang dalam proses verifikasi tim kami.</p>
                </div>
            @else
                <!-- Vertical Card List (Mobile-First Thumb Friendly) -->
                <div class="space-y-3">
                    @foreach($caregivers as $caregiver)
                        <div class="bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-soft hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start gap-3">
                                <!-- Avatar with Verified Badge -->
                                <div class="relative shrink-0">
                                    @if($caregiver->photo)
                                        <img src="{{ asset('storage/' . $caregiver->photo) }}" alt="{{ $caregiver->user->name }}" class="w-16 h-16 rounded-2xl object-cover ring-1 ring-slate-200">
                                    @else
                                        <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-500 border border-rose-100 flex items-center justify-center">
                                            <x-lucide-user class="w-8 h-8 stroke-[1.8]" />
                                        </div>
                                    @endif
                                    <span class="absolute -bottom-1 -right-1 w-5 h-5 bg-white rounded-full flex items-center justify-center shadow-xs">
                                        <x-lucide-badge-check class="w-4 h-4 text-emerald-500 fill-emerald-100" />
                                    </span>
                                </div>

                                <!-- Caregiver Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1">
                                        <h3 class="text-sm font-bold text-slate-900 truncate">
                                            <a href="{{ route('caregivers.show', $caregiver) }}" class="hover:text-brand">
                                                {{ $caregiver->user->name }}
                                            </a>
                                        </h3>
                                        <div class="flex items-center gap-0.5 text-xs font-bold text-slate-800 shrink-0">
                                            <x-lucide-star class="w-3.5 h-3.5 text-amber-400 fill-amber-400" />
                                            <span>{{ number_format($caregiver->rating, 1) }}</span>
                                        </div>
                                    </div>

                                    <!-- Service Area -->
                                    <div class="flex items-center gap-1 text-[11px] text-slate-500 mt-0.5">
                                        <x-lucide-map-pin class="w-3 h-3 text-slate-400 shrink-0" />
                                        <span class="truncate">{{ $caregiver->service_area ?? 'Jabodetabek' }}</span>
                                    </div>

                                    <!-- Skills Pills -->
                                    @if($caregiver->skills)
                                        <div class="flex flex-wrap gap-1 mt-1.5">
                                            @foreach(array_slice(explode(',', $caregiver->skills), 0, 2) as $skill)
                                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-medium">
                                                    {{ trim($skill) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Bottom Price & Booking Action -->
                            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] text-slate-400 block leading-none">Tarif Layanan</span>
                                    <div class="flex items-baseline gap-0.5 mt-0.5">
                                        <span class="text-sm font-extrabold text-brand">Rp {{ number_format($caregiver->hourly_rate, 0, ',', '.') }}</span>
                                        <span class="text-[10px] text-slate-400">/ jam</span>
                                    </div>
                                </div>
                                <a href="{{ route('caregivers.show', $caregiver) }}" class="inline-flex items-center justify-center px-4 py-1.5 rounded-xl bg-brand text-white text-xs font-bold hover:bg-brand-hover shadow-xs active:scale-95 transition-all">
                                    Pilih Jadwal
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <!-- Keunggulan Layanan BookingBoo -->
        <section class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 px-1">Keamanan & Jaminan Layanan</h2>
            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <x-lucide-shield-check class="w-4 h-4 stroke-[2.5]" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900">Verifikasi Dokumen Resmi</h4>
                        <p class="text-[11px] text-slate-500 leading-normal">Setiap caregiver diperiksa KTP, sertifikat keperawatan/STR, dan riwayat kesehatan.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center shrink-0">
                        <x-lucide-clock-3 class="w-4 h-4 stroke-[2.5]" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900">Pemesanan Fleksibel Per Jam</h4>
                        <p class="text-[11px] text-slate-500 leading-normal">Cukup bayar sesuai durasi aktual pendampingan tanpa biaya tersembunyi.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <x-lucide-messages-square class="w-4 h-4 stroke-[2.5]" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900">Koordinasi Chat Langsung</h4>
                        <p class="text-[11px] text-slate-500 leading-normal">Komunikasi langsung dengan caregiver untuk briefing kondisi pasien sebelum tiba.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mitra Banner / Footer -->
        <section class="text-center py-2">
            <p class="text-xs text-slate-500">Anda seorang tenaga medis atau perawat berizin?</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-1 text-xs font-bold text-brand hover:underline mt-1">
                Daftar Menjadi Caregiver BookingBoo
                <x-lucide-arrow-right class="w-3.5 h-3.5" />
            </a>
        </section>
    </div>
</x-mobile-layout>
