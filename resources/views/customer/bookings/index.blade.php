<x-app-layout>
    <!-- Halodoc Mobile Header -->
    <div class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-slate-100 px-4 py-3.5 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="w-9 h-9 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors">
                <x-lucide-arrow-left class="w-5 h-5" />
            </a>
            <div>
                <h1 class="text-base font-bold text-slate-900 leading-tight">Riwayat Booking</h1>
                <p class="text-[11px] text-slate-500">Kelola reservasi pendamping medis Anda</p>
            </div>
        </div>
        <a href="{{ route('caregivers.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 text-rose-600 border border-rose-200/80 rounded-full text-xs font-semibold hover:bg-rose-100 transition-colors">
            <x-lucide-calendar-plus class="w-3.5 h-3.5" />
            <span>Booking Baru</span>
        </a>
    </div>

    <div class="px-4 py-4 space-y-4" x-data="{ activeFilter: 'all' }">
        <!-- Status Filter Pills (Halodoc horizontal scroll chips) -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 text-xs font-medium">
            <button type="button" @click="activeFilter = 'all'"
                :class="activeFilter === 'all' ? 'bg-rose-500 text-white shadow-sm shadow-rose-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/70'"
                class="px-3.5 py-1.5 rounded-full whitespace-nowrap transition-all">
                Semua
            </button>
            <button type="button" @click="activeFilter = 'active'"
                :class="activeFilter === 'active' ? 'bg-rose-500 text-white shadow-sm shadow-rose-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/70'"
                class="px-3.5 py-1.5 rounded-full whitespace-nowrap transition-all">
                Aktif / Berjalan
            </button>
            <button type="button" @click="activeFilter = 'requested'"
                :class="activeFilter === 'requested' ? 'bg-rose-500 text-white shadow-sm shadow-rose-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/70'"
                class="px-3.5 py-1.5 rounded-full whitespace-nowrap transition-all">
                Menunggu Konfirmasi
            </button>
            <button type="button" @click="activeFilter = 'completed'"
                :class="activeFilter === 'completed' ? 'bg-rose-500 text-white shadow-sm shadow-rose-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/70'"
                class="px-3.5 py-1.5 rounded-full whitespace-nowrap transition-all">
                Selesai
            </button>
            <button type="button" @click="activeFilter = 'cancelled'"
                :class="activeFilter === 'cancelled' ? 'bg-rose-500 text-white shadow-sm shadow-rose-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/70'"
                class="px-3.5 py-1.5 rounded-full whitespace-nowrap transition-all">
                Dibatalkan
            </button>
        </div>

        @if ($bookings->isEmpty())
            <!-- Halodoc Empty State -->
            <div class="py-16 text-center px-4 bg-slate-50/60 rounded-3xl border border-dashed border-slate-200 mt-2">
                <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-3">
                    <x-lucide-receipt class="w-8 h-8" />
                </div>
                <h2 class="text-base font-bold text-slate-800">Belum Ada Booking</h2>
                <p class="text-xs text-slate-500 max-w-xs mx-auto mt-1">Cari perawat atau caregiver terverifikasi untuk mendampingi keluarga Anda sekarang.</p>
                <div class="mt-5">
                    <a href="{{ route('caregivers.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-rose-500 text-white font-semibold text-xs rounded-xl shadow-md shadow-rose-200 hover:bg-rose-600 transition-all">
                        <x-lucide-search class="w-4 h-4" />
                        Cari Caregiver Sekarang
                    </a>
                </div>
            </div>
        @else
            <!-- Halodoc Booking Cards List -->
            <div class="space-y-3.5">
                @foreach ($bookings as $booking)
                    @php
                        $filterCategory = match($booking->status) {
                            'requested' => 'requested',
                            'accepted', 'confirmed', 'in_progress' => 'active',
                            'completed' => 'completed',
                            default => 'cancelled',
                        };

                        $statusBadge = match($booking->status) {
                            'requested' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'dot' => 'bg-amber-400'],
                            'accepted' => ['bg' => 'bg-sky-50', 'text' => 'text-sky-700', 'border' => 'border-sky-200', 'dot' => 'bg-sky-500'],
                            'confirmed' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'dot' => 'bg-blue-500'],
                            'in_progress' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200', 'dot' => 'bg-purple-500 animate-pulse'],
                            'completed' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500'],
                            default => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'border' => 'border-rose-200', 'dot' => 'bg-rose-400'],
                        };
                    @endphp
                    <div x-show="activeFilter === 'all' || activeFilter === '{{ $filterCategory }}'"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm hover:shadow-md transition-all">
                        
                        <!-- Header: Booking Code & Status -->
                        <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-1.5 text-xs text-slate-500 font-mono">
                                <x-lucide-receipt class="w-3.5 h-3.5 text-slate-400" />
                                <span>{{ $booking->booking_code }}</span>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $statusBadge['bg'] }} {{ $statusBadge['text'] }} {{ $statusBadge['border'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }}"></span>
                                {{ $booking->label() }}
                            </span>
                        </div>

                        <!-- Body: Caregiver Profile & Schedule -->
                        <div class="py-3 flex items-start gap-3">
                            <div class="relative shrink-0">
                                @if($booking->caregiver->photo)
                                    <img src="{{ asset('storage/' . $booking->caregiver->photo) }}" alt="{{ $booking->caregiver->user->name }}" class="w-12 h-12 rounded-xl object-cover border border-slate-100">
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-rose-100 to-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm border border-rose-100">
                                        {{ substr($booking->caregiver->user->name, 0, 2) }}
                                    </div>
                                @endif
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 text-white flex items-center justify-center ring-2 ring-white">
                                    <x-lucide-check class="w-2.5 h-2.5 stroke-[3]" />
                                </div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-bold text-slate-900 truncate flex items-center gap-1.5">
                                    {{ $booking->caregiver->user->name }}
                                </h3>
                                <p class="text-xs text-slate-500 truncate flex items-center gap-1 mt-0.5">
                                    <x-lucide-map-pin class="w-3 h-3 text-slate-400 shrink-0" />
                                    {{ $booking->location ?: ($booking->caregiver->service_area ?: 'Area Layanan') }}
                                </p>
                                
                                <div class="mt-2 flex items-center gap-3 text-xs text-slate-600 font-medium">
                                    <span class="flex items-center gap-1 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-100">
                                        <x-lucide-calendar class="w-3 h-3 text-rose-500" />
                                        {{ $booking->start_time->format('d M Y') }}
                                    </span>
                                    <span class="flex items-center gap-1 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-100">
                                        <x-lucide-clock class="w-3 h-3 text-slate-400" />
                                        {{ $booking->start_time->format('H:i') }} - {{ $booking->end_time->format('H:i') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer: Amount & Action Buttons -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                            <div>
                                <p class="text-[10px] text-slate-400 font-medium">Total Tagihan</p>
                                <p class="text-sm font-extrabold text-slate-900">
                                    Rp {{ number_format($booking->total_amount > 0 ? $booking->total_amount : $booking->estimateTotal(), 0, ',', '.') }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('customer.bookings.invoice', $booking) }}" class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors" title="Lihat Invoice">
                                    <x-lucide-file-text class="w-4 h-4" />
                                </a>
                                <a href="{{ route('customer.bookings.show', $booking) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-rose-500 text-white rounded-xl text-xs font-semibold hover:bg-rose-600 transition-colors shadow-sm shadow-rose-200">
                                    <span>Detail</span>
                                    <x-lucide-chevron-right class="w-3.5 h-3.5" />
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
</x-app-layout>