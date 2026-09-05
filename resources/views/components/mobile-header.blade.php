@props(['showSearch' => true, 'placeholder' => 'Cari caregiver, pendamping RS, rawat lansia...'])

<div class="bg-white px-4 pt-3.5 pb-3 border-b border-slate-100 sticky top-0 z-30 shadow-xs">
    <!-- Top Bar: Darurat (Kiri), Logo (Tengah), Profile (Pojok Kanan Atas) -->
    <div class="relative flex items-center justify-between mb-2.5 min-h-[36px]">
        <!-- Kiri: Bantuan Darurat -->
        <div class="flex items-center z-10">
            <a href="{{ route('help') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-semibold transition-colors shrink-0" title="Bantuan Darurat">
                <x-lucide-phone-call class="w-3.5 h-3.5" />
                <span class="hidden sm:inline">Bantuan</span> Darurat
            </a>
        </div>

        <!-- Tengah: Logo BookingBoo -->
        <div class="absolute left-1/2 -translate-x-1/2 flex items-center justify-center pointer-events-auto">
            <a href="{{ url('/') }}" class="flex items-center shrink-0" title="Beranda BookingBoo">
                <x-application-logo class="h-7 w-auto" />
            </a>
        </div>
        
        <!-- Kanan: Notifikasi + Profile Avatar (Pojok Kanan Atas) -> Menuju /dashboard -->
        <div class="flex items-center gap-1.5 z-10">
            @auth
                <a href="{{ route('notifications.index') }}" class="relative w-9 h-9 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 hover:text-rose-600 hover:border-rose-200 transition-all shrink-0" title="Notifikasi">
                    <x-lucide-bell class="w-4 h-4" />
                    @if(auth()->user()->unreadNotificationsCount() > 0)
                        <span class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-rose-500 text-white text-[9px] font-bold flex items-center justify-center ring-2 ring-white">
                            {{ auth()->user()->unreadNotificationsCount() > 9 ? '9+' : auth()->user()->unreadNotificationsCount() }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('dashboard') }}" class="relative shrink-0 group block" title="Buka Dashboard">
                    @if(auth()->user()->isCaregiver() && auth()->user()->caregiver?->photo)
                        <img src="{{ asset('storage/' . auth()->user()->caregiver->photo) }}" alt="{{ auth()->user()->name }}" class="w-9 h-9 rounded-full object-cover ring-2 ring-rose-200 group-hover:ring-rose-400 transition-all">
                    @else
                        <div class="w-9 h-9 rounded-full bg-rose-500 text-white flex items-center justify-center font-bold text-xs shadow-sm shadow-rose-200 group-hover:ring-2 group-hover:ring-rose-400 transition-all">
                            {{ substr(auth()->user()->name, 0, 2) }}
                        </div>
                    @endif
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-white"></div>
                </a>
            @else
                <a href="{{ route('dashboard') }}" class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shrink-0" title="Masuk / Dashboard">
                    <x-lucide-user class="w-4 h-4" />
                </a>
            @endauth
        </div>
    </div>

    <!-- Location Bar -->
    <div class="flex items-center justify-between text-xs text-slate-600 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200/60 mb-2.5">
        <div class="flex items-center gap-1.5 truncate">
            <x-lucide-map-pin class="w-3.5 h-3.5 text-brand shrink-0" />
            <span class="text-slate-500">Area Layanan:</span>
            <span class="font-semibold text-slate-800 truncate">Jabodetabek & Sekitarnya</span>
        </div>
        <a href="{{ route('caregivers.index') }}" class="text-brand font-medium hover:underline shrink-0 text-[11px]">Ubah</a>
    </div>

    @if($showSearch)
        <!-- Search Bar Kapsul -->
        <form action="{{ route('caregivers.index') }}" method="GET" class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <x-lucide-search class="w-4 h-4 text-slate-400" />
            </div>
            <input type="text" name="q" value="{{ request('q') }}"
                placeholder="{{ $placeholder }}"
                class="w-full pl-10 pr-4 py-2.5 bg-slate-100/80 hover:bg-slate-100 focus:bg-white text-sm text-slate-800 placeholder-slate-400 rounded-full border-0 ring-1 ring-slate-200/80 focus:ring-2 focus:ring-brand transition-all">
        </form>
    @endif
</div>
