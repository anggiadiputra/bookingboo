<x-app-layout>
    <!-- Halodoc Mobile Header Bar -->
    <div class="bg-gradient-to-b from-rose-500/10 via-rose-500/5 to-transparent px-4 pt-5 pb-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-full bg-rose-500 text-white flex items-center justify-center font-bold text-sm shadow-md shadow-rose-200">
                    {{ substr(auth()->user()->name, 0, 2) }}
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <h1 class="text-base font-extrabold text-slate-900 leading-tight">Halo, {{ auth()->user()->name }} 👋</h1>
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <p class="text-xs text-slate-500">Keluarga sehat, hati tenang</p>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-[10px] font-bold text-indigo-700">
                            <x-lucide-id-card class="w-3 h-3" />
                            {{ auth()->user()->public_id }}
                        </span>
                    </div>
                </div>
            </div>
            <a href="{{ route('help') }}" class="w-9 h-9 rounded-full bg-white border border-rose-100 shadow-sm flex items-center justify-center text-rose-500 hover:bg-rose-50 transition-colors" title="Bantuan Darurat">
                <x-lucide-bell class="w-4 h-4" />
            </a>
        </div>

        <!-- Halodoc Quick Search Trigger Bar -->
        <a href="{{ route('caregivers.index') }}" class="mt-4 flex items-center gap-3 bg-white border border-slate-200/80 rounded-2xl px-4 py-3 shadow-sm hover:border-rose-300 hover:shadow-md transition-all">
            <x-lucide-search class="w-4 h-4 text-rose-500 shrink-0" />
            <span class="text-xs text-slate-400 font-medium flex-1">Cari perawat lansia, fisioterapis, pendamping...</span>
            <span class="px-2 py-0.5 rounded-lg bg-rose-50 text-rose-600 text-[10px] font-bold">Cari</span>
        </a>
    </div>

    <div class="px-4 py-3 space-y-5">
        <!-- Halodoc 4-Grid Medical Menu -->
        <div class="grid grid-cols-4 gap-2.5 text-center">
            <a href="{{ route('caregivers.index') }}" class="group flex flex-col items-center">
                <div class="w-14 h-14 rounded-2xl bg-rose-50 border border-rose-100/80 flex items-center justify-center text-rose-600 shadow-sm group-hover:bg-rose-500 group-hover:text-white transition-all">
                    <x-lucide-user-check class="w-6 h-6 transition-transform group-hover:scale-110" />
                </div>
                <span class="mt-2 text-[11px] font-bold text-slate-700 leading-tight">Cari Caregiver</span>
            </a>

            <a href="{{ route('customer.bookings.index') }}" class="group flex flex-col items-center">
                <div class="w-14 h-14 rounded-2xl bg-sky-50 border border-sky-100/80 flex items-center justify-center text-sky-600 shadow-sm group-hover:bg-sky-500 group-hover:text-white transition-all">
                    <x-lucide-calendar class="w-6 h-6 transition-transform group-hover:scale-110" />
                </div>
                <span class="mt-2 text-[11px] font-bold text-slate-700 leading-tight">Booking Saya</span>
            </a>

            <a href="{{ route('customer.profile.edit') }}" class="group flex flex-col items-center">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-100/80 flex items-center justify-center text-emerald-600 shadow-sm group-hover:bg-emerald-500 group-hover:text-white transition-all">
                    <x-lucide-user-cog class="w-6 h-6 transition-transform group-hover:scale-110" />
                </div>
                <span class="mt-2 text-[11px] font-bold text-slate-700 leading-tight">Profil Pasien</span>
            </a>

            <a href="{{ route('help') }}" class="group flex flex-col items-center">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 border border-amber-100/80 flex items-center justify-center text-amber-600 shadow-sm group-hover:bg-amber-500 group-hover:text-white transition-all">
                    <x-lucide-life-buoy class="w-6 h-6 transition-transform group-hover:scale-110" />
                </div>
                <span class="mt-2 text-[11px] font-bold text-slate-700 leading-tight">Bantuan 24/7</span>
            </a>
        </div>

        <!-- Halodoc Emergency 24/7 Banner Card -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-rose-600 to-rose-500 p-4 text-white shadow-lg shadow-rose-200">
            <div class="flex items-center justify-between relative z-10">
                <div class="space-y-1">
                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-white/20 text-[10px] font-bold backdrop-blur">
                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                        <span>Hotline Siaga 24/7</span>
                    </div>
                    <h2 class="text-sm font-extrabold leading-snug">Butuh Bantuan Cepat atau Darurat?</h2>
                    <p class="text-[11px] text-rose-100">Tim kami siap siaga membantu kebutuhan medis mendesak Anda.</p>
                </div>
                <a href="{{ route('help') }}" class="shrink-0 px-3.5 py-2 rounded-xl bg-white text-rose-600 text-xs font-bold shadow-sm hover:bg-rose-50 transition-colors">
                    Hubungi
                </a>
            </div>
        </div>

        <!-- Halodoc Caregiver Favorit Section -->
        <div class="pt-1">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <x-lucide-heart class="w-4 h-4 text-rose-500 fill-current" />
                    <h2 class="text-sm font-bold text-slate-900">Caregiver Favorit</h2>
                </div>
                <a href="{{ route('caregivers.index') }}" class="text-xs font-semibold text-rose-500 hover:text-rose-600 flex items-center gap-1">
                    <span>Lihat Semua</span>
                    <x-lucide-chevron-right class="w-3.5 h-3.5" />
                </a>
            </div>

            @if($favorites->isEmpty())
                <div class="p-6 text-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50">
                    <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-400 flex items-center justify-center mx-auto mb-2">
                        <x-lucide-heart class="w-6 h-6" />
                    </div>
                    <p class="text-xs font-bold text-slate-700">Belum ada caregiver favorit</p>
                    <p class="text-[11px] text-slate-400 mt-0.5 max-w-xs mx-auto">Tandai caregiver dengan ikon hati saat mencari agar dapat diakses lebih cepat.</p>
                    <a href="{{ route('caregivers.index') }}" class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-rose-500 hover:text-rose-600">
                        <span>Cari Caregiver Sekarang</span>
                        <x-lucide-arrow-right class="w-3.5 h-3.5" />
                    </a>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($favorites as $favorite)
                        <div class="bg-white rounded-2xl border border-slate-200/80 p-3.5 shadow-sm hover:shadow-md transition-all">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="relative shrink-0">
                                        @if($favorite->photo)
                                            <img src="{{ asset('storage/' . $favorite->photo) }}" alt="{{ $favorite->user->name }}" class="w-12 h-12 rounded-xl object-cover border border-slate-100">
                                        @else
                                            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-rose-100 to-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm">
                                                {{ substr($favorite->user->name, 0, 2) }}
                                            </div>
                                        @endif
                                        <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 text-white flex items-center justify-center ring-2 ring-white">
                                            <x-lucide-check class="w-2.5 h-2.5 stroke-[3]" />
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-sm text-slate-900 truncate">{{ $favorite->user->name }}</h3>
                                        <p class="text-xs text-slate-500 truncate flex items-center gap-1 mt-0.5">
                                            <x-lucide-map-pin class="w-3 h-3 text-slate-400" />
                                            {{ $favorite->service_area ?: 'Area Layanan' }}
                                        </p>
                                        <div class="mt-1 flex items-center gap-1">
                                            <x-lucide-star class="w-3.5 h-3.5 text-amber-400 fill-current" />
                                            <span class="text-xs font-bold text-slate-800">{{ number_format($favorite->rating, 1) }}</span>
                                            <span class="text-[10px] text-slate-400">({{ $favorite->reviews_count ?? 0 }})</span>
                                        </div>
                                    </div>
                                </div>
                                <form action="{{ route('customer.favorites.toggle', $favorite) }}" method="POST">
                                    @csrf
                                    <button type="submit" title="Hapus dari favorit" class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl transition-colors">
                                        <x-lucide-heart class="w-5 h-5 fill-current" />
                                    </button>
                                </form>
                            </div>

                            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-[10px] text-slate-400">Tarif Mulai</p>
                                    <p class="text-xs font-extrabold text-slate-900">
                                        Rp {{ number_format($favorite->hourly_rate ?? 0, 0, ',', '.') }}<span class="text-[10px] font-normal text-slate-500">/jam</span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('caregivers.show', $favorite) }}" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                                        Profil
                                    </a>
                                    <a href="{{ route('customer.bookings.create', $favorite) }}" class="px-3.5 py-1.5 rounded-xl bg-rose-500 text-white text-xs font-semibold hover:bg-rose-600 transition-colors shadow-sm shadow-rose-200">
                                        Booking
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
