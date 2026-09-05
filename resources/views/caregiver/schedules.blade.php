<x-app-layout>
    <!-- Halodoc Mobile Header -->
    <div class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-slate-100 px-4 py-3.5 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="w-9 h-9 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors">
                <x-lucide-arrow-left class="w-5 h-5" />
            </a>
            <div>
                <h1 class="text-base font-bold text-slate-900 leading-tight">Jadwal Ketersediaan</h1>
                <p class="text-[11px] text-slate-500">Atur jam & tanggal Anda siap menerima tugas</p>
            </div>
        </div>
    </div>

    <div class="px-4 py-4 space-y-4">
        <!-- Form Tambah Slot -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <x-lucide-plus-circle class="w-4 h-4" />
                </div>
                <h2 class="text-xs font-bold text-slate-900">Tambah Slot Jadwal Siaga</h2>
            </div>
            
            <form method="POST" action="{{ route('caregiver.schedules.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="start_time" class="block text-xs font-semibold text-slate-700 mb-1">Waktu Mulai Siaga</label>
                        <input id="start_time" type="datetime-local" name="start_time" value="{{ old('start_time') }}" required
                            class="w-full rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 text-xs px-3 py-2.5" />
                        <x-input-error :messages="$errors->get('start_time')" class="mt-1" />
                    </div>
                    <div>
                        <label for="end_time" class="block text-xs font-semibold text-slate-700 mb-1">Waktu Selesai Siaga</label>
                        <input id="end_time" type="datetime-local" name="end_time" value="{{ old('end_time') }}" required
                            class="w-full rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 text-xs px-3 py-2.5" />
                        <x-input-error :messages="$errors->get('end_time')" class="mt-1" />
                    </div>
                </div>

                <div class="pt-1">
                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-rose-500 text-white font-bold text-xs shadow-md shadow-rose-200 hover:bg-rose-600 transition-all">
                        <x-lucide-calendar-plus class="w-4 h-4" />
                        <span>Simpan Slot Ketersediaan</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Daftar Slot Ketersediaan -->
        <div class="space-y-2.5">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider px-1">
                Daftar Slot Anda
            </h2>

            @if ($schedules->isEmpty())
                <div class="p-8 text-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50">
                    <x-lucide-calendar-x class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                    <p class="text-xs font-bold text-slate-700">Belum Ada Slot Jadwal</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Tambahkan slot waktu di atas agar pasien dapat memesan layanan Anda.</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($schedules as $schedule)
                        <div class="bg-white rounded-2xl border border-slate-200/80 p-3.5 shadow-sm flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex flex-col items-center justify-center text-slate-700 shrink-0">
                                    <span class="text-[10px] font-bold leading-none text-rose-500 uppercase">{{ $schedule->start_time->format('M') }}</span>
                                    <span class="text-sm font-extrabold leading-none mt-0.5">{{ $schedule->start_time->format('d') }}</span>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-900">
                                        {{ $schedule->start_time->format('H:i') }} - {{ $schedule->end_time->format('H:i') }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if ($schedule->status === 'available')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Tersedia
                                            </span>
                                        @elseif ($schedule->status === 'booked')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                Terbooking
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">
                                                Diblokir
                                            </span>
                                        @endif
                                        <span class="text-[10px] text-slate-400">
                                            {{ $schedule->start_time->diffInHours($schedule->end_time) }} jam
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                @if ($schedule->status !== 'booked')
                                    <form method="POST" action="{{ route('caregiver.schedules.destroy', $schedule) }}" onsubmit="return confirm('Hapus slot ketersediaan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-xl text-rose-500 hover:bg-rose-50 transition-colors" title="Hapus Slot">
                                            <x-lucide-trash-2 class="w-4 h-4" />
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-300 p-2">
                                        <x-lucide-lock class="w-4 h-4" />
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $schedules->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
