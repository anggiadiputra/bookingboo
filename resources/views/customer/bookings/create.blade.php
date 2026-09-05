<x-mobile-layout title="Pilih Jadwal Booking">
    <!-- Top Header Bar -->
    <div class="bg-white px-4 py-3.5 border-b border-slate-100 sticky top-0 z-30 shadow-xs flex items-center justify-between">
        <a href="{{ route('caregivers.show', $caregiver) }}" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-colors">
            <x-lucide-arrow-left class="w-4 h-4" />
        </a>
        <h1 class="text-sm font-bold text-slate-900">Pilih Jadwal Caregiver</h1>
        <div class="w-8"></div>
    </div>

    @php
        $rate = (float) $caregiver->hourly_rate;
        // Generate next 7 days for the pill selector
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $d = now()->addDays($i);
            $dates[] = [
                'val' => $d->format('Y-m-d'),
                'day' => $i === 0 ? 'Hari ini' : ($i === 1 ? 'Besok' : $d->locale('id')->isoFormat('ddd')),
                'date' => $d->locale('id')->isoFormat('D MMM'),
            ];
        }
        $timeSlots = ['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
        $defaultDate = $dates[0]['val'];
        $defaultTime = '09:00';
    @endphp

    <div class="p-4 space-y-4 pb-32" 
         x-data="bookingScheduler({{ $rate }}, '{{ $defaultDate }}', '{{ $defaultTime }}', 3)">
        
        <!-- Caregiver Summary Card (Mockup 2 Header) -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-soft flex items-center gap-3">
            <div class="relative shrink-0">
                @if($caregiver->photo)
                    <img src="{{ asset('storage/' . $caregiver->photo) }}" alt="{{ $caregiver->user->name }}" class="w-14 h-14 rounded-2xl object-cover ring-1 ring-slate-200">
                @else
                    <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-500 border border-rose-100 flex items-center justify-center">
                        <x-lucide-user class="w-7 h-7 stroke-[1.8]" />
                    </div>
                @endif
                <span class="absolute -bottom-1 -right-1 w-5 h-5 bg-white rounded-full flex items-center justify-center shadow-xs">
                    <x-lucide-badge-check class="w-4 h-4 text-emerald-500 fill-emerald-100" />
                </span>
            </div>

            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-bold text-slate-900 truncate">{{ $caregiver->user->name }}</h3>
                <p class="text-xs text-slate-500 truncate">Caregiver Medis & Lansia</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="inline-flex items-center gap-0.5 text-xs font-bold text-slate-800">
                        <x-lucide-star class="w-3.5 h-3.5 text-amber-400 fill-amber-400" />
                        {{ number_format($caregiver->rating, 1) }}
                    </span>
                    <span class="text-slate-300">&middot;</span>
                    <span class="text-xs font-extrabold text-brand">Rp {{ number_format($rate, 0, ',', '.') }}<span class="text-[10px] text-slate-400 font-normal">/jam</span></span>
                </div>
            </div>
        </div>

        <form id="booking-form" method="POST" action="{{ route('customer.bookings.store', $caregiver) }}" class="space-y-4">
            @csrf

            <!-- Hidden inputs computed by Alpine for backend -->
            <input type="hidden" name="start_time" :value="startDateTime">
            <input type="hidden" name="end_time" :value="endDateTime">

            <!-- 1. Horizontal Date Picker Pills -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-soft">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2.5 flex items-center gap-1.5">
                    <x-lucide-calendar class="w-4 h-4 text-brand" />
                    Pilih Tanggal Layanan
                </label>
                
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                    @foreach($dates as $d)
                        <button type="button" 
                                @click="selectedDate = '{{ $d['val'] }}'; updateTimes();"
                                :class="selectedDate === '{{ $d['val'] }}' ? 'bg-brand text-white border-brand shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                                class="px-3.5 py-2 rounded-2xl border text-center shrink-0 transition-all">
                            <span class="block text-[11px] font-medium" :class="selectedDate === '{{ $d['val'] }}' ? 'text-rose-100' : 'text-slate-400'">{{ $d['day'] }}</span>
                            <span class="block text-xs font-bold mt-0.5">{{ $d['date'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- 2. Time Slot Chips Grid (Mockup 2 Style) -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-soft">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2.5 flex items-center gap-1.5">
                    <x-lucide-clock class="w-4 h-4 text-brand" />
                    Pilih Jam Mulai
                </label>

                <div class="grid grid-cols-4 gap-2">
                    @foreach($timeSlots as $slot)
                        <button type="button" 
                                @click="selectedTime = '{{ $slot }}'; updateTimes();"
                                :class="selectedTime === '{{ $slot }}' ? 'bg-brand text-white border-brand font-bold shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 font-medium'"
                                class="py-2.5 px-2 rounded-xl border text-xs text-center transition-all">
                            {{ $slot }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- 3. Durasi Pendampingan Stepper -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-soft flex items-center justify-between">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                        <x-lucide-hourglass class="w-4 h-4 text-brand" />
                        Durasi Pendampingan
                    </label>
                    <p class="text-[11px] text-slate-400 mt-0.5" x-text="'Selesai perkiraan: ' + endTimeDisplay"></p>
                </div>

                <div class="flex items-center gap-3 bg-slate-50 px-3 py-1.5 rounded-2xl border border-slate-200">
                    <button type="button" @click="if(duration > 1) { duration--; updateTimes(); }"
                            class="w-7 h-7 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-700 hover:bg-slate-100 font-bold active:scale-90 transition-all">
                        <x-lucide-minus class="w-3.5 h-3.5" />
                    </button>
                    <span class="text-sm font-extrabold text-slate-900 w-12 text-center" x-text="duration + ' Jam'"></span>
                    <button type="button" @click="if(duration < 12) { duration++; updateTimes(); }"
                            class="w-7 h-7 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-700 hover:bg-slate-100 font-bold active:scale-90 transition-all">
                        <x-lucide-plus class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            <!-- 4. Lokasi & Catatan Pasien -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-soft space-y-3">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                    <x-lucide-map-pin class="w-4 h-4 text-brand" />
                    Detail Lokasi & Kebutuhan
                </label>

                <div>
                    <label for="location" class="block text-xs font-semibold text-slate-700 mb-1">Lokasi Pertemuan / Rumah Sakit</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-lucide-building-2 class="w-4 h-4 text-slate-400" />
                        </div>
                        <input type="text" name="location" id="location" value="{{ old('location') }}" required
                            placeholder="mis. RS Cipto Mangunkusumo / Alamat Rumah"
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
                    </div>
                </div>

                <div>
                    <label for="needs" class="block text-xs font-semibold text-slate-700 mb-1">Kebutuhan Pasien & Catatan Medis</label>
                    <textarea name="needs" id="needs" rows="3"
                        placeholder="Jelaskan kondisi pasien (mis. butuh kursi roda, pendampingan pasca kemo, dsb)..."
                        class="w-full px-3 py-2 text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">{{ old('needs') }}</textarea>
                </div>
            </div>
        </form>

        <!-- Sticky Bottom Bar (Ala Halodoc Mockup 2) -->
        <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur border-t border-slate-200/80 shadow-nav w-full max-w-md mx-auto px-4 py-3 flex items-center justify-between">
            <div>
                <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">Total Estimasi</span>
                <div class="flex items-baseline gap-0.5">
                    <span class="text-lg font-extrabold text-brand" x-text="formatRupiah(totalCost)"></span>
                    <span class="text-[10px] text-slate-400" x-text="'(' + duration + ' jam)'"></span>
                </div>
            </div>

            <button type="button" @click="document.getElementById('booking-form').submit()"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-brand text-white font-bold text-sm hover:bg-brand-hover shadow-xs active:scale-95 transition-all">
                <x-lucide-arrow-right class="w-4 h-4" />
                Lanjut Pembayaran
            </button>
        </div>
    </div>

    @push('scripts')
    <script>
        function bookingScheduler(ratePerHour, initialDate, initialTime, initialDuration) {
            return {
                hourlyRate: ratePerHour,
                selectedDate: initialDate,
                selectedTime: initialTime,
                duration: initialDuration,
                startDateTime: '',
                endDateTime: '',
                endTimeDisplay: '',
                totalCost: ratePerHour * initialDuration,

                init() {
                    this.updateTimes();
                },

                updateTimes() {
                    this.totalCost = this.hourlyRate * this.duration;
                    
                    // Construct startDateTime: YYYY-MM-DDTHH:MM
                    const startStr = `${this.selectedDate}T${this.selectedTime}`;
                    this.startDateTime = startStr;

                    // Calculate end time
                    const startDate = new Date(`${this.selectedDate} ${this.selectedTime}:00`);
                    const endDate = new Date(startDate.getTime() + this.duration * 60 * 60 * 1000);

                    // End time ISO format YYYY-MM-DDTHH:MM
                    const pad = (n) => String(n).padStart(2, '0');
                    const endYear = endDate.getFullYear();
                    const endMonth = pad(endDate.getMonth() + 1);
                    const endDay = pad(endDate.getDate());
                    const endHours = pad(endDate.getHours());
                    const endMins = pad(endDate.getMinutes());

                    this.endDateTime = `${endYear}-${endMonth}-${endDay}T${endHours}:${endMins}`;
                    this.endTimeDisplay = `${endHours}:${endMins}`;
                },

                formatRupiah(val) {
                    return 'Rp ' + Number(val).toLocaleString('id-ID');
                }
            };
        }
    </script>
    @endpush
</x-mobile-layout>