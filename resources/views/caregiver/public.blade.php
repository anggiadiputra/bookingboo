<x-mobile-layout :title="$caregiver->user->name">
    <!-- Top Header with Back Navigation -->
    <div class="bg-white px-4 py-3.5 border-b border-slate-100 sticky top-0 z-30 shadow-xs flex items-center justify-between">
        <a href="{{ route('caregivers.index') }}" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-colors">
            <x-lucide-arrow-left class="w-4 h-4" />
        </a>
        <h1 class="text-sm font-bold text-slate-900 truncate max-w-[200px]">Profil Caregiver</h1>
        <div class="w-8"></div> <!-- spacer for balance -->
    </div>

    <div class="p-4 space-y-4 pb-28">
        <!-- Main Caregiver Card (Mockup Style) -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-soft text-center relative overflow-hidden">
            <!-- Background Decorative Wave -->
            <div class="absolute inset-x-0 top-0 h-20 bg-gradient-to-b from-rose-50 to-white -z-0"></div>

            <div class="relative z-10 flex flex-col items-center">
                <!-- Avatar with Verified Badge -->
                <div class="relative mb-3">
                    @if($caregiver->photo)
                        <img src="{{ asset('storage/' . $caregiver->photo) }}" alt="{{ $caregiver->user->name }}" class="w-24 h-24 rounded-full object-cover ring-4 ring-white shadow-md">
                    @else
                        <div class="w-24 h-24 rounded-full bg-rose-50 text-rose-500 border-2 border-rose-100 flex items-center justify-center shadow-md">
                            <x-lucide-user class="w-12 h-12 stroke-[1.8]" />
                        </div>
                    @endif
                    <span class="absolute bottom-0 right-1 w-6 h-6 bg-white rounded-full flex items-center justify-center shadow-xs">
                        <x-lucide-badge-check class="w-5 h-5 text-emerald-500 fill-emerald-100" />
                    </span>
                </div>

                <h2 class="text-lg font-bold text-slate-900">{{ $caregiver->user->name }}</h2>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-[11px] font-bold text-indigo-700 mt-1">
                    <x-lucide-id-card class="w-3.5 h-3.5" />
                    {{ $caregiver->user->public_id }}
                </span>
                <p class="text-xs text-slate-500 mt-0.5">Caregiver Medis & Pendamping Lansia</p>

                <!-- Rating & Experience Pills -->
                <div class="flex items-center gap-2 mt-2.5">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 text-xs font-bold border border-amber-200/60">
                        <x-lucide-star class="w-3.5 h-3.5 text-amber-500 fill-amber-500" />
                        {{ number_format($rating, 1) }} ({{ $reviews->total() }} ulasan)
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200/60">
                        <x-lucide-shield-check class="w-3.5 h-3.5" />
                        Terverifikasi
                    </span>
                </div>

                @auth
                    @if(auth()->user()->isCustomer())
                        @php
                            $isFav = auth()->user()->customer?->favoriteCaregivers->contains($caregiver->id) ?? false;
                        @endphp
                        <div class="mt-3">
                            <form action="{{ route('customer.favorites.toggle', $caregiver) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full border text-xs font-semibold transition-all {{ $isFav ? 'bg-rose-50 border-rose-200 text-rose-600' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50 shadow-xs' }}">
                                    <x-lucide-heart class="w-3.5 h-3.5 {{ $isFav ? 'fill-current text-rose-500' : 'text-slate-400' }}" />
                                    <span>{{ $isFav ? 'Hapus dari Favorit' : 'Tambah ke Favorit' }}</span>
                                </button>
                            </form>
                        </div>
                    @endif
                @endauth

                <!-- Rate Tag & Service Area -->
                <div class="grid grid-cols-2 gap-2 w-full mt-4 pt-4 border-t border-slate-100 text-left">
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold block">Tarif Layanan</span>
                        <span class="text-sm font-extrabold text-brand">Rp {{ number_format($caregiver->hourly_rate, 0, ',', '.') }}</span>
                        <span class="text-[10px] text-slate-400">/ jam</span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold block">Area Jangkauan</span>
                        <span class="text-xs font-bold text-slate-800 truncate block mt-0.5">{{ $caregiver->service_area ?? 'Jabodetabek' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bio / Tentang Section -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-soft">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                <x-lucide-user-check class="w-4 h-4 text-brand" />
                Tentang Caregiver
            </h3>
            <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">
                {{ $caregiver->bio ?: 'Caregiver berpengalaman dan berdedikasi tinggi dalam mendampingi pasien berobat, kontrol rumah sakit, dan perawatan harian lansia.' }}
            </p>
        </div>

        <!-- Keahlian / Spesialisasi Section -->
        @if($caregiver->skills)
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-soft">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                    <x-lucide-stethoscope class="w-4 h-4 text-brand" />
                    Keahlian & Spesialisasi
                </h3>
                <div class="flex flex-wrap gap-1.5">
                    @foreach(explode(',', $caregiver->skills) as $skill)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl bg-slate-100 text-slate-700 text-xs font-medium">
                            <x-lucide-check class="w-3 h-3 text-emerald-600" />
                            {{ trim($skill) }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Ulasan Pasien Section -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-soft">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                    <x-lucide-message-square class="w-4 h-4 text-brand" />
                    Ulasan Pasien ({{ $reviews->total() }})
                </h3>
                @if($reviews->total() > 0)
                    <span class="text-xs font-bold text-amber-500 flex items-center gap-0.5">
                        <x-lucide-star class="w-3.5 h-3.5 fill-current" />
                        {{ number_format($rating, 1) }} / 5.0
                    </span>
                @endif
            </div>

            @if($reviews->isEmpty())
                <p class="text-xs text-slate-400 py-3 text-center">Belum ada ulasan untuk caregiver ini.</p>
            @else
                <div class="space-y-3 divide-y divide-slate-100">
                    @foreach($reviews as $review)
                        <div class="pt-2.5 first:pt-0">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-800">{{ $review->reviewer->name ?? 'Pasien' }}</span>
                                <div class="flex items-center text-amber-400">
                                    @for($i = 1; $i <= 5; $i++)
                                        <x-lucide-star class="w-3 h-3 {{ $i <= $review->rating ? 'fill-current' : 'text-slate-200' }}" />
                                    @endfor
                                </div>
                            </div>
                            @if($review->comment)
                                <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ $review->comment }}</p>
                            @endif
                            <span class="text-[10px] text-slate-400 block mt-1">{{ $review->created_at->format('d M Y') }}</span>
                        </div>
                    @endforeach
                </div>

                @if($reviews->hasPages())
                    <div class="mt-3">
                        {{ $reviews->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Sticky Bottom Action Bar (Ala Halodoc) -->
    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur border-t border-slate-200/80 shadow-nav w-full max-w-md mx-auto px-4 py-3 flex items-center justify-between">
        <div>
            <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">Tarif per jam</span>
            <div class="flex items-baseline gap-0.5">
                <span class="text-base font-extrabold text-brand">Rp {{ number_format($caregiver->hourly_rate, 0, ',', '.') }}</span>
                <span class="text-xs text-slate-500">/ jam</span>
            </div>
        </div>

        @if(auth()->check() && auth()->user()->isCustomer())
            <a href="{{ route('customer.bookings.create', $caregiver) }}" 
               class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-brand text-white font-bold text-sm hover:bg-brand-hover shadow-sm active:scale-95 transition-all">
                <x-lucide-calendar-plus class="w-4 h-4" />
                Booking Sekarang
            </a>
        @elseif(!auth()->check())
            <a href="{{ route('login') }}" 
               class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-brand text-white font-bold text-sm hover:bg-brand-hover shadow-sm active:scale-95 transition-all">
                <x-lucide-calendar-plus class="w-4 h-4" />
                Masuk untuk Booking
            </a>
        @else
            <span class="text-xs text-slate-400">Akun Anda adalah {{ auth()->user()->role }}</span>
        @endif
    </div>
</x-mobile-layout>
