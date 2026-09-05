<x-app-layout>
    <!-- Halodoc Caregiver Mobile Header -->
    <div class="bg-gradient-to-b from-rose-500/10 via-rose-500/5 to-transparent px-4 pt-5 pb-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                    @if(auth()->user()->caregiver && auth()->user()->caregiver->photo)
                        <img src="{{ asset('storage/' . auth()->user()->caregiver->photo) }}" alt="{{ auth()->user()->name }}" class="w-12 h-12 rounded-2xl object-cover border border-slate-100 shadow-sm">
                    @else
                        <div class="w-12 h-12 rounded-2xl bg-rose-500 text-white flex items-center justify-center font-bold text-sm shadow-md shadow-rose-200">
                            {{ substr(auth()->user()->name, 0, 2) }}
                        </div>
                    @endif
                    <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 text-white flex items-center justify-center ring-2 ring-white">
                        <x-lucide-check class="w-2.5 h-2.5 stroke-[3]" />
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <h1 class="text-base font-extrabold text-slate-900 leading-tight">{{ auth()->user()->name }}</h1>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-[10px] font-bold text-indigo-700">
                            <x-lucide-id-card class="w-3 h-3" />
                            {{ auth()->user()->public_id }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Mitra Caregiver Aktif</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('help') }}" class="w-9 h-9 rounded-full bg-white border border-slate-200 shadow-sm flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" title="Bantuan">
                    <x-lucide-life-buoy class="w-4 h-4 text-rose-500" />
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Keluar"
                            class="w-9 h-9 rounded-full bg-white border border-slate-200 shadow-sm flex items-center justify-center text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-colors">
                        <x-lucide-log-out class="w-4 h-4" />
                    </button>
                </form>
            </div>
        </div>

        <!-- Quick Summary Stats Card -->
        @if(auth()->user()->caregiver)
            <div class="mt-4 grid grid-cols-3 gap-2 bg-white border border-slate-200/80 rounded-2xl p-3 shadow-sm text-center">
                <div class="p-1">
                    <span class="text-[10px] text-slate-400 font-medium block">Rating</span>
                    <div class="flex items-center justify-center gap-1 mt-0.5">
                        <x-lucide-star class="w-3.5 h-3.5 text-amber-400 fill-current" />
                        <span class="text-xs font-bold text-slate-800">{{ number_format(auth()->user()->caregiver->rating ?? 5.0, 1) }}</span>
                    </div>
                </div>
                <div class="p-1 border-x border-slate-100">
                    <span class="text-[10px] text-slate-400 font-medium block">Tarif / Jam</span>
                    <span class="text-xs font-bold text-slate-800 mt-0.5 block">
                        Rp {{ number_format((auth()->user()->caregiver->hourly_rate ?? 0) / 1000, 0) }}k
                    </span>
                </div>
                <div class="p-1">
                    <span class="text-[10px] text-slate-400 font-medium block">Pengalaman</span>
                    <span class="text-xs font-bold text-slate-800 mt-0.5 block">
                        {{ auth()->user()->caregiver->years_of_experience ?? 1 }}+ Thn
                    </span>
                </div>
            </div>
        @endif
    </div>

    <div class="px-4 py-3 space-y-5">
        <!-- Halodoc 4-Grid Medical Menu Caregiver -->
        <div class="grid grid-cols-4 gap-2.5 text-center">
            <a href="{{ route('caregiver.bookings.index') }}" class="group flex flex-col items-center">
                <div class="w-14 h-14 rounded-2xl bg-rose-50 border border-rose-100/80 flex items-center justify-center text-rose-600 shadow-sm group-hover:bg-rose-500 group-hover:text-white transition-all">
                    <x-lucide-inbox class="w-6 h-6 transition-transform group-hover:scale-110" />
                </div>
                <span class="mt-2 text-[11px] font-bold text-slate-700 leading-tight">Booking Masuk</span>
            </a>

            <a href="{{ route('caregiver.schedules.index') }}" class="group flex flex-col items-center">
                <div class="w-14 h-14 rounded-2xl bg-sky-50 border border-sky-100/80 flex items-center justify-center text-sky-600 shadow-sm group-hover:bg-sky-500 group-hover:text-white transition-all">
                    <x-lucide-calendar-days class="w-6 h-6 transition-transform group-hover:scale-110" />
                </div>
                <span class="mt-2 text-[11px] font-bold text-slate-700 leading-tight">Jadwal Slot</span>
            </a>

            <a href="{{ route('caregiver.documents.index') }}" class="group flex flex-col items-center">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-100/80 flex items-center justify-center text-emerald-600 shadow-sm group-hover:bg-emerald-500 group-hover:text-white transition-all">
                    <x-lucide-file-check class="w-6 h-6 transition-transform group-hover:scale-110" />
                </div>
                <span class="mt-2 text-[11px] font-bold text-slate-700 leading-tight">Dokumen STR</span>
            </a>

            <a href="{{ route('caregiver.payouts.index') }}" class="group flex flex-col items-center">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 border border-amber-100/80 flex items-center justify-center text-amber-600 shadow-sm group-hover:bg-amber-500 group-hover:text-white transition-all">
                    <x-lucide-wallet class="w-6 h-6 transition-transform group-hover:scale-110" />
                </div>
                <span class="mt-2 text-[11px] font-bold text-slate-700 leading-tight">Dompet Payout</span>
            </a>
        </div>

        <!-- Profil & Layanan Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                    <x-lucide-user-cog class="w-4 h-4 text-rose-500" />
                    <span>Profil & Pengaturan Layanan</span>
                </h2>
                <a href="{{ route('caregiver.profile.edit') }}" class="text-xs font-semibold text-rose-500 hover:text-rose-600 flex items-center gap-1">
                    <span>Edit</span>
                    <x-lucide-chevron-right class="w-3.5 h-3.5" />
                </a>
            </div>

            @if(auth()->user()->caregiver)
                <div class="space-y-2 text-xs text-slate-600 pt-1">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Area Layanan:</span>
                        <span class="font-semibold text-slate-800">{{ auth()->user()->caregiver->service_area ?: 'Belum diatur' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Tarif / Jam:</span>
                        <span class="font-semibold text-slate-800">Rp {{ number_format(auth()->user()->caregiver->hourly_rate ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @if(auth()->user()->caregiver->skills)
                        <div class="pt-1">
                            <span class="text-[10px] text-slate-400 font-medium block mb-1.5">Keahlian Medis:</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach(is_array(auth()->user()->caregiver->skills) ? auth()->user()->caregiver->skills : explode(',', (string) auth()->user()->caregiver->skills) as $skill)
                                    @if(trim($skill))
                                        <span class="px-2 py-0.5 rounded-lg bg-slate-50 border border-slate-100 text-[11px] font-medium text-slate-600">
                                            {{ trim($skill) }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Emergency Support Banner for Caregiver -->
        <div class="rounded-2xl bg-slate-900 p-4 text-white shadow-md flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-1.5 text-rose-400 text-xs font-bold">
                    <x-lucide-siren class="w-4 h-4" />
                    <span>Bantuan Darurat di Lapangan</span>
                </div>
                <p class="text-[11px] text-slate-300">Mengalami kendala mendesak atau situasi darurat saat pendampingan pasien?</p>
            </div>
            <a href="{{ route('help') }}" class="shrink-0 px-3.5 py-2 rounded-xl bg-rose-500 text-white text-xs font-bold hover:bg-rose-600 transition-colors">
                Hotline 24/7
            </a>
        </div>
    </div>
</x-app-layout>
