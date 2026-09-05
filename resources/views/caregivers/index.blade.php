<x-mobile-layout title="Cari Caregiver">
    <!-- Top Header Bar -->
    <div class="bg-white px-4 pt-3.5 pb-3 border-b border-slate-100 sticky top-0 z-30 shadow-xs">
        <div class="flex items-center gap-3 mb-3">
            <a href="{{ url('/') }}" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-colors">
                <x-lucide-arrow-left class="w-4 h-4" />
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base font-bold text-slate-900 leading-tight">Cari Caregiver</h1>
                <p class="text-[11px] text-slate-500 truncate">Temukan pendamping medis & lansia terverifikasi</p>
            </div>
            @if(request()->hasAny(['q', 'area', 'min_rate', 'max_rate', 'sort', 'lat', 'lng']))
                <a href="{{ route('caregivers.index') }}" class="text-xs font-semibold text-brand hover:underline shrink-0">
                    Reset
                </a>
            @endif
        </div>

        <!-- Search Bar Input -->
        <form method="GET" action="{{ route('caregivers.index') }}" class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <x-lucide-search class="w-4 h-4 text-slate-400" />
            </div>
            <input type="text" name="q" id="q" value="{{ request('q') }}"
                placeholder="Nama nakes, keahlian (mis. luka, infus)..."
                class="w-full pl-10 pr-10 py-2.5 bg-slate-100/80 hover:bg-slate-100 focus:bg-white text-sm text-slate-800 placeholder-slate-400 rounded-full border-0 ring-1 ring-slate-200/80 focus:ring-2 focus:ring-brand transition-all">
            @if(request('q'))
                <a href="{{ route('caregivers.index', request()->except('q')) }}" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600">
                    <x-lucide-x class="w-4 h-4" />
                </a>
            @endif
            <!-- Preserve other filters -->
            @foreach(['area', 'min_rate', 'max_rate', 'sort', 'lat', 'lng'] as $f)
                @if(request($f))
                    <input type="hidden" name="{{ $f }}" value="{{ request($f) }}">
                @endif
            @endforeach
        </form>

        <!-- Horizontal Scrollable Quick Filter Chips -->
        <div class="flex items-center gap-2 mt-3 overflow-x-auto no-scrollbar pb-0.5 text-xs"
            x-data="gpsSearch()"
            @gps-error.window="showError($event.detail)">
            <!-- Filter: Gunakan Lokasi Saya (GPS) -->
            @if(request('lat') && request('lng'))
                <a href="{{ route('caregivers.index', array_merge(request()->except(['page', 'lat', 'lng']), ['sort' => 'distance', 'lat' => request('lat'), 'lng' => request('lng')])) }}"
                    class="px-3 py-1.5 rounded-full border shrink-0 flex items-center gap-1 transition-all font-medium bg-rose-50 text-brand border-rose-200">
                    <x-lucide-locate-fixed class="w-3.5 h-3.5 text-brand" />
                    Terdekat
                </a>
            @else
                <button type="button" @click="locate()"
                    class="px-3 py-1.5 rounded-full border shrink-0 flex items-center gap-1 transition-all font-medium bg-white text-slate-600 border-slate-200 hover:bg-slate-50">
                    <x-lucide-locate-fixed class="w-3.5 h-3.5 text-brand" x-bind:class="loading ? 'animate-pulse' : ''" />
                    <span x-text="loading ? 'Mendeteksi...' : 'Gunakan Lokasi Saya'">Gunakan Lokasi Saya</span>
                </button>
            @endif

            <!-- Filter: Semua -->
            <a href="{{ route('caregivers.index', request()->only('q')) }}"
                class="px-3 py-1.5 rounded-full border shrink-0 transition-all font-medium {{ !request('sort') && !request('area') ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                Semua
            </a>

            <!-- Filter: Rating Tertinggi -->
            <a href="{{ route('caregivers.index', array_merge(request()->except('page'), ['sort' => 'rating'])) }}"
                class="px-3 py-1.5 rounded-full border shrink-0 flex items-center gap-1 transition-all font-medium {{ request('sort', 'rating') === 'rating' ? 'bg-rose-50 text-brand border-rose-200' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                <x-lucide-star class="w-3.5 h-3.5 text-amber-400 fill-current" />
                Rating Tertinggi
            </a>

            <!-- Filter: Tarif Terendah -->
            <a href="{{ route('caregivers.index', array_merge(request()->except('page'), ['sort' => 'rate_asc'])) }}"
                class="px-3 py-1.5 rounded-full border shrink-0 flex items-center gap-1 transition-all font-medium {{ request('sort') === 'rate_asc' ? 'bg-rose-50 text-brand border-rose-200' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                <x-lucide-arrow-down-wide-narrow class="w-3.5 h-3.5" />
                Tarif Terendah
            </a>

            <!-- Filter: Tarif Tertinggi -->
            <a href="{{ route('caregivers.index', array_merge(request()->except('page'), ['sort' => 'rate_desc'])) }}"
                class="px-3 py-1.5 rounded-full border shrink-0 flex items-center gap-1 transition-all font-medium {{ request('sort') === 'rate_desc' ? 'bg-rose-50 text-brand border-rose-200' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                <x-lucide-arrow-up-narrow-wide class="w-3.5 h-3.5" />
                Tarif Tertinggi
            </a>
        </div>

        <!-- GPS error toast -->
        <div x-show="error" x-cloak class="mt-2 flex items-center gap-2 px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 text-[11px] text-amber-800">
            <x-lucide-locate-off class="w-3.5 h-3.5 shrink-0 text-amber-500" />
            <span x-text="error"></span>
        </div>
    </div>

    <div class="p-4 space-y-4">
        <!-- Collapsible Detailed Filter Bar -->
        <div x-data="{ filterOpen: {{ request()->hasAny(['area', 'min_rate', 'max_rate']) ? 'true' : 'false' }} }" class="bg-white rounded-2xl border border-slate-200/80 shadow-soft overflow-hidden">
            <button @click="filterOpen = !filterOpen" type="button" class="w-full px-4 py-2.5 flex items-center justify-between text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                <span class="flex items-center gap-2">
                    <x-lucide-sliders-horizontal class="w-3.5 h-3.5 text-brand" />
                    Filter Lanjutan (Area & Tarif)
                </span>
                <span class="flex items-center gap-1 text-slate-400">
                    <span x-text="filterOpen ? 'Sembunyikan' : 'Buka'"></span>
                    <span class="inline-block transition-transform duration-200" :class="filterOpen ? 'rotate-180' : ''">
                        <x-lucide-chevron-down class="w-3.5 h-3.5" />
                    </span>
                </span>
            </button>

            <form x-show="filterOpen" method="GET" action="{{ route('caregivers.index') }}" class="p-4 pt-2 border-t border-slate-100 space-y-3 bg-slate-50/50" style="display: none;">
                @if(request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif
                @if(request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif

                <div>
                    <label for="area" class="block text-xs font-semibold text-slate-700 mb-1">Area Layanan</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-lucide-map-pin class="w-3.5 h-3.5 text-slate-400" />
                        </div>
                        <input type="text" name="area" id="area" value="{{ request('area') }}"
                            placeholder="mis. Jakarta Selatan, Depok"
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand bg-white">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="min_rate" class="block text-xs font-semibold text-slate-700 mb-1">Tarif Min</label>
                        <input type="number" name="min_rate" id="min_rate" value="{{ request('min_rate') }}" min="0" step="5000"
                            placeholder="Rp 0"
                            class="w-full px-3 py-2 text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand bg-white">
                    </div>
                    <div>
                        <label for="max_rate" class="block text-xs font-semibold text-slate-700 mb-1">Tarif Maks</label>
                        <input type="number" name="max_rate" id="max_rate" value="{{ request('max_rate') }}" min="0" step="5000"
                            placeholder="Tidak terbatas"
                            class="w-full px-3 py-2 text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand bg-white">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <a href="{{ route('caregivers.index') }}" class="px-3 py-1.5 text-xs text-slate-600 hover:text-slate-900">Reset Filter</a>
                    <button type="submit" class="px-4 py-1.5 rounded-xl bg-brand text-white text-xs font-bold hover:bg-brand-hover shadow-xs">
                        Terapkan Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Result Stats -->
        <div class="flex items-center justify-between px-1 text-xs text-slate-500">
            <span>Ditemukan <strong>{{ $caregivers->total() }}</strong> caregiver terverifikasi</span>
        </div>

        <!-- Caregivers List -->
        @if($caregivers->isEmpty())
            <!-- Empty State -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-8 text-center shadow-soft">
                <x-lucide-search-x class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                <h3 class="text-sm font-bold text-slate-800">Tidak ada caregiver ditemukan</h3>
                <p class="text-xs text-slate-500 mt-1">Coba ubah kata kunci atau hapus beberapa filter pencarian.</p>
                <a href="{{ route('caregivers.index') }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-brand text-white text-xs font-bold hover:bg-brand-hover shadow-xs">
                    <x-lucide-refresh-cw class="w-3.5 h-3.5" />
                    Reset Pencarian
                </a>
            </div>
        @else
            <div class="space-y-3">
                @foreach($caregivers as $caregiver)
                    <div class="bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-soft hover:shadow-md transition-all">
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

                                <div class="flex items-center gap-1 text-[11px] text-slate-500 mt-0.5">
                                    <x-lucide-map-pin class="w-3 h-3 text-slate-400 shrink-0" />
                                    <span class="truncate">{{ $caregiver->service_area ?? 'Jabodetabek' }}</span>
                                    @if(isset($caregiver->distance_km) && $caregiver->distance_km !== null)
                                        <span class="shrink-0 px-1.5 py-0.5 rounded-md bg-rose-50 text-brand text-[10px] font-bold whitespace-nowrap">
                                            ± {{ $caregiver->distance_km < 1 ? number_format($caregiver->distance_km * 1000, 0) . ' m' : number_format($caregiver->distance_km, 1) . ' km' }}
                                        </span>
                                    @endif
                                </div>

                                @if($caregiver->skills)
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach(array_slice(explode(',', $caregiver->skills), 0, 3) as $skill)
                                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-medium">
                                                {{ trim($skill) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Price & CTA Action -->
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

            <!-- Pagination -->
            <div class="mt-4">
                {{ $caregivers->links() }}
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('gpsSearch', () => ({
                loading: false,
                error: '',
                locate() {
                    if (this.loading) return;
                    if (!navigator.geolocation) {
                        this.showError('Perangkat Anda tidak mendukung deteksi lokasi.');
                        return;
                    }
                    this.loading = true;
                    this.error = '';
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            const params = new URLSearchParams(window.location.search);
                            params.delete('page');
                            params.set('lat', pos.coords.latitude.toFixed(6));
                            params.set('lng', pos.coords.longitude.toFixed(6));
                            params.set('sort', 'distance');
                            window.location.href = '{{ route('caregivers.index') }}?' + params.toString();
                        },
                        (err) => {
                            this.loading = false;
                            if (err.code === err.PERMISSION_DENIED) {
                                this.showError('Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser untuk mencari caregiver terdekat.');
                            } else if (err.code === err.TIMEOUT) {
                                this.showError('Deteksi lokasi memakan waktu terlalu lama. Coba lagi.');
                            } else {
                                this.showError('Lokasi tidak dapat dideteksi. Coba lagi.');
                            }
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                    );
                },
                showError(message) {
                    this.error = message;
                    this.loading = false;
                },
            }));
        });
    </script>
</x-mobile-layout>