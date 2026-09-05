<!-- Partial detail booking, dipakai customer & caregiver. $booking, $viewer (customer|caregiver) -->
@php
    $isCustomer = $viewer === 'customer';
    $other = $isCustomer ? $booking->caregiver : $booking->customer;
    $otherName = $other->user->name;
    $estimate = $booking->estimateTotal();
    $showActions = $isCustomer
        ? in_array($booking->status, [\App\Models\Booking::STATUS_REQUESTED, \App\Models\Booking::STATUS_ACCEPTED], true)
        : $booking->status === \App\Models\Booking::STATUS_REQUESTED;
    $showCaregiverCancel = ! $isCustomer
        && in_array($booking->status, [\App\Models\Booking::STATUS_ACCEPTED, \App\Models\Booking::STATUS_CONFIRMED], true);
    $showCheckIn = ! $isCustomer
        && $booking->status === \App\Models\Booking::STATUS_CONFIRMED
        && ! $booking->check_in_at
        && now()->gte($booking->start_time);
    $showCheckOut = ! $isCustomer
        && $booking->status === \App\Models\Booking::STATUS_IN_PROGRESS
        && ! $booking->check_out_at;
    $actualMinutes = ($booking->check_in_at && $booking->check_out_at)
        ? (int) round($booking->check_in_at->diffInMinutes($booking->check_out_at))
        : null;
    // Kontak darurat pasien hanya untuk caregiver saat layanan sudah dikonfirmasi/berjalan
    // (FR-27): data sensitif tidak boleh bocor sebelum layanan disetujui.
    $showEmergencyContact = ! $isCustomer
        && in_array($booking->status, [\App\Models\Booking::STATUS_CONFIRMED, \App\Models\Booking::STATUS_IN_PROGRESS], true)
        && $booking->customer->emergency_contact;

    $statusBadge = match($booking->status) {
        'requested' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'dot' => 'bg-amber-400'],
        'accepted' => ['bg' => 'bg-sky-50', 'text' => 'text-sky-700', 'border' => 'border-sky-200', 'dot' => 'bg-sky-500'],
        'confirmed' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'dot' => 'bg-blue-500'],
        'in_progress' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200', 'dot' => 'bg-purple-500 animate-pulse'],
        'completed' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500'],
        default => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'border' => 'border-rose-200', 'dot' => 'bg-rose-400'],
    };
@endphp

<div class="space-y-4">
    <!-- 1. Header Card: Status & Stepper Tracker -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Status Reservasi</span>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border {{ $statusBadge['bg'] }} {{ $statusBadge['text'] }} {{ $statusBadge['border'] }}">
                        <span class="w-2 h-2 rounded-full {{ $statusBadge['dot'] }}"></span>
                        {{ $booking->label() }}
                    </span>
                </div>
            </div>
            <div class="text-right">
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">No. Booking</span>
                <p class="text-xs font-mono font-bold text-slate-800 mt-0.5">{{ $booking->booking_code }}</p>
            </div>
        </div>

        <!-- Halodoc Stepper Progress -->
        @php
            $stepIcons = [
                \App\Models\Booking::STATUS_REQUESTED => 'lucide-calendar-plus',
                \App\Models\Booking::STATUS_ACCEPTED => 'lucide-check',
                \App\Models\Booking::STATUS_CONFIRMED => 'lucide-check-circle-2',
                \App\Models\Booking::STATUS_IN_PROGRESS => 'lucide-clock',
                \App\Models\Booking::STATUS_COMPLETED => 'lucide-calendar-check',
            ];
            $stepStyles = [
                'done' => ['ring' => 'bg-rose-500 border-rose-500 text-white', 'icon' => 'text-white', 'label' => 'text-rose-600 font-bold'],
                'current' => ['ring' => 'bg-rose-50 border-2 border-rose-500 text-rose-600', 'icon' => 'text-rose-600', 'label' => 'text-rose-600 font-bold'],
                'pending' => ['ring' => 'bg-slate-50 border-slate-200 text-slate-300', 'icon' => 'text-slate-300', 'label' => 'text-slate-400 font-medium'],
            ];
        @endphp
        <div class="pt-4 pb-1">
            <ol class="flex items-start">
                @foreach($booking->progressSteps() as $i => $step)
                    @php $style = $stepStyles[$step['state']]; @endphp
                    <li class="flex flex-col items-center flex-1 relative">
                        <div class="w-8 h-8 rounded-full border flex items-center justify-center text-xs font-bold z-10 {{ $style['ring'] }}">
                            <x-dynamic-component :component="$stepIcons[$step['status']]" class="w-3.5 h-3.5" />
                        </div>
                        <span class="text-[10px] text-center mt-1.5 leading-tight {{ $style['label'] }} px-0.5">
                            {{ $step['label'] }}
                        </span>
                        @if($i < 4)
                            <div class="absolute top-4 left-1/2 w-full h-0.5 -z-0 {{ $step['state'] === 'done' ? 'bg-rose-500' : 'bg-slate-200' }}"></div>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    <!-- 2. Caregiver / Customer Info Profile Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
            {{ $isCustomer ? 'Caregiver Pendamping' : 'Pasien / Pemesan' }}
        </h2>
        <div class="flex items-start gap-3">
            <div class="relative shrink-0">
                @if($other->photo ?? false)
                    <img src="{{ asset('storage/' . $other->photo) }}" alt="{{ $otherName }}" class="w-13 h-13 rounded-2xl object-cover border border-slate-100 shadow-sm">
                @else
                    <div class="w-13 h-13 rounded-2xl bg-gradient-to-tr from-rose-100 to-rose-50 text-rose-600 flex items-center justify-center font-bold text-base border border-rose-100">
                        {{ substr($otherName, 0, 2) }}
                    </div>
                @endif
                <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 text-white flex items-center justify-center ring-2 ring-white">
                    <x-lucide-check class="w-2.5 h-2.5 stroke-[3]" />
                </div>
            </div>

            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-bold text-slate-900 truncate">{{ $otherName }}</h3>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-[10px] font-bold text-indigo-700 mt-0.5">
                    <x-lucide-id-card class="w-3 h-3 shrink-0" />
                    {{ $other->user->public_id }}
                </span>
                @if($isCustomer)
                    <p class="text-xs text-slate-500 truncate flex items-center gap-1 mt-0.5">
                        <x-lucide-map-pin class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                        {{ $booking->caregiver->service_area ?: 'Area Jabodetabek' }}
                    </p>
                    <div class="mt-1.5 flex items-center gap-1.5">
                        <div class="flex items-center gap-0.5 text-xs font-bold text-slate-800">
                            <x-lucide-star class="w-3.5 h-3.5 text-amber-400 fill-current" />
                            <span>{{ number_format($booking->caregiver->rating, 1) }}</span>
                        </div>
                        <span class="text-slate-300">&middot;</span>
                        <span class="text-[11px] text-slate-500">{{ $booking->caregiver->years_of_experience ?? 3 }}+ thn pengalaman</span>
                    </div>
                @else
                    <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                        <x-lucide-mail class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                        {{ $other->user->email }}
                    </p>
                @endif
            </div>

            @if($isCustomer)
                <a href="{{ route('caregivers.show', $booking->caregiver) }}" class="shrink-0 px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                    Profil
                </a>
            @endif
        </div>
    </div>

    <!-- 3. Jadwal & Biaya Layanan Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3.5">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
            Rincian Jadwal & Layanan
        </h2>

        <div class="grid grid-cols-2 gap-3 pt-1">
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                <div class="flex items-center gap-1.5 text-slate-500 text-xs font-medium">
                    <x-lucide-calendar class="w-3.5 h-3.5 text-rose-500" />
                    <span>Tanggal</span>
                </div>
                <p class="text-xs font-bold text-slate-900 mt-1">
                    {{ $booking->start_time->format('d M Y') }}
                </p>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                <div class="flex items-center gap-1.5 text-slate-500 text-xs font-medium">
                    <x-lucide-clock class="w-3.5 h-3.5 text-rose-500" />
                    <span>Waktu & Durasi</span>
                </div>
                <p class="text-xs font-bold text-slate-900 mt-1">
                    {{ $booking->start_time->format('H:i') }} - {{ $booking->end_time->format('H:i') }}
                    <span class="text-[10px] text-slate-500 font-normal">({{ $booking->start_time->diffInHours($booking->end_time) }} jam)</span>
                </p>
            </div>
        </div>

        @if($booking->location)
            <div class="flex items-start gap-2.5 pt-1 text-xs text-slate-700">
                <x-lucide-map-pin class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
                <div>
                    <p class="text-[10px] text-slate-400 font-medium">Alamat / Lokasi Layanan</p>
                    <p class="font-medium text-slate-900 mt-0.5">{{ $booking->location }}</p>
                </div>
            </div>
        @endif

        @if($booking->needs)
            <div class="flex items-start gap-2.5 pt-1 text-xs text-slate-700">
                <x-lucide-file-text class="w-4 h-4 text-sky-500 shrink-0 mt-0.5" />
                <div>
                    <p class="text-[10px] text-slate-400 font-medium">Catatan / Kebutuhan Medis Pasien</p>
                    <p class="font-medium text-slate-800 mt-0.5 whitespace-pre-line">{{ $booking->needs }}</p>
                </div>
            </div>
        @endif

        @if($showEmergencyContact)
            <div class="flex items-start gap-2.5 p-3 rounded-xl bg-rose-50/80 border border-rose-100">
                <x-lucide-phone-call class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
                <div class="text-xs">
                    <p class="text-[10px] text-rose-600 font-bold uppercase tracking-wide">Kontak Darurat Pasien</p>
                    <p class="font-bold text-slate-900 mt-0.5">{{ $booking->customer->emergency_contact }}</p>
                    @if($booking->customer->emergency_contact_phone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $booking->customer->emergency_contact_phone) }}"
                           class="text-xs text-rose-600 font-semibold hover:underline inline-flex items-center gap-1 mt-0.5">
                            <x-lucide-phone class="w-3 h-3" />
                            {{ $booking->customer->emergency_contact_phone }}
                        </a>
                    @endif
                </div>
            </div>
        @endif

        @if($booking->cancellation_reason)
            <div class="flex items-start gap-2.5 p-3 rounded-xl bg-rose-50 border border-rose-200">
                <x-lucide-alert-circle class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                <div class="text-xs">
                    <p class="font-bold text-rose-800">Alasan Pembatalan / Penolakan</p>
                    <p class="text-rose-700 mt-0.5">{{ $booking->cancellation_reason }}</p>
                </div>
            </div>
        @endif

        @if($booking->needs_replacement)
            <div class="flex items-start gap-2.5 p-3 rounded-xl bg-amber-50 border border-amber-200">
                <x-lucide-refresh-ccw class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                <div class="text-xs">
                    <p class="font-bold text-amber-800">Caregiver Pengganti Sedang Diproses</p>
                    <p class="text-amber-700 mt-0.5">Tim support kami sedang menugaskan caregiver pengganti. Anda akan diberi tahu begitu pengganti terkonfirmasi.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- 4. Rincian Biaya & Invoice Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
            Rincian Pembayaran
        </h2>
        <div class="space-y-2 text-xs">
            <div class="flex items-center justify-between text-slate-600">
                <span>Tarif Caregiver</span>
                <span class="font-semibold text-slate-800">Rp {{ number_format($booking->caregiver->hourly_rate ?? 0, 0, ',', '.') }}/jam</span>
            </div>
            <div class="flex items-center justify-between text-slate-600">
                <span>Durasi Layanan</span>
                <span class="font-semibold text-slate-800">{{ $booking->start_time->diffInHours($booking->end_time) }} Jam</span>
            </div>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                <span class="font-bold text-slate-900">Total Tagihan</span>
                <span class="text-base font-extrabold text-rose-600">
                    Rp {{ number_format($booking->total_amount > 0 ? $booking->total_amount : $estimate, 0, ',', '.') }}
                </span>
            </div>

            @if(in_array($booking->status, [\App\Models\Booking::STATUS_CANCELLED], true) && $isCustomer)
                <div class="mt-3 p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-xs">
                    <p class="font-bold text-emerald-800">Rincian Refund</p>
                    <div class="flex items-center justify-between text-emerald-700 mt-1">
                        <span>Refund {{ $booking->refund_percent }}%</span>
                        <span class="font-extrabold">Rp {{ number_format($booking->refund_amount, 0, ',', '.') }}</span>
                    </div>
                    @if((float) $booking->cancellation_fee > 0)
                        <p class="text-[10px] text-emerald-600 mt-1">Biaya pembatalan: Rp {{ number_format($booking->cancellation_fee, 0, ',', '.') }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- 5. Catatan Check-in / Check-out Aktual (Caregiver) -->
    @if($booking->check_in_at || $booking->check_out_at)
        <div class="bg-gradient-to-r from-sky-50 to-blue-50 rounded-2xl border border-sky-100 p-4">
            <div class="flex items-center gap-2 mb-2">
                <x-lucide-clock-3 class="w-4 h-4 text-sky-600" />
                <h3 class="text-xs font-bold text-sky-900">Waktu Layanan Aktual</h3>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="bg-white/80 p-2.5 rounded-xl border border-sky-100">
                    <span class="text-[10px] text-slate-400 font-medium">Check-in:</span>
                    <p class="font-bold text-slate-800 mt-0.5">{{ $booking->check_in_at ? $booking->check_in_at->format('d M Y, H:i') : '-' }}</p>
                </div>
                <div class="bg-white/80 p-2.5 rounded-xl border border-sky-100">
                    <span class="text-[10px] text-slate-400 font-medium">Check-out:</span>
                    <p class="font-bold text-slate-800 mt-0.5">{{ $booking->check_out_at ? $booking->check_out_at->format('H:i') : 'Berjalan...' }}</p>
                </div>
            </div>
            @if($actualMinutes !== null)
                @php $actualLabel = $actualMinutes >= 60
                    ? intdiv($actualMinutes, 60).' jam '.($actualMinutes % 60).' menit'
                    : $actualMinutes.' menit'; @endphp
                <p class="mt-2 text-center text-xs font-bold text-sky-800 bg-sky-100/70 py-1.5 rounded-lg">
                    Durasi Layanan Selesai: {{ $actualLabel }}
                </p>
            @endif
        </div>
    @endif

    <!-- 6. Aksi & Tombol Status Booking -->
    @if($showActions)
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
            @if($isCustomer)
                <form method="POST" action="{{ route('customer.bookings.cancel', $booking) }}"
                    onsubmit="return confirm('Batalkan booking ini?')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl border border-rose-200 text-rose-600 font-semibold text-xs hover:bg-rose-50 transition-colors">
                        <x-lucide-x class="w-4 h-4" />
                        <span>Batalkan Booking</span>
                    </button>
                </form>
            @else
                <div class="space-y-3">
                    <form method="POST" action="{{ route('caregiver.bookings.accept', $booking) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-rose-500 text-white font-bold text-xs shadow-md shadow-rose-200 hover:bg-rose-600 transition-all">
                            <x-lucide-check class="w-4 h-4" />
                            <span>Terima Tugas Booking</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('caregiver.bookings.reject', $booking) }}"
                        onsubmit="return confirm('Tolak booking ini?')">
                        @csrf
                        @method('PATCH')
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Penolakan</label>
                        <textarea name="reason" rows="2" required
                            class="w-full rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 text-xs placeholder:text-slate-400 mb-2"
                            placeholder="mis. Jadwal bentrok dengan penugasan lain..."></textarea>
                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border border-slate-200 text-slate-600 font-semibold text-xs hover:bg-slate-50 transition-colors">
                            <x-lucide-x class="w-4 h-4" />
                            <span>Tolak Booking</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @endif

    @if($showCaregiverCancel)
        <!-- Batalkan booking yang sudah disetujui (caregiver) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
            <h3 class="text-xs font-bold text-slate-800 mb-2">Batalkan Penugasan Layanan</h3>
            <form method="POST" action="{{ route('caregiver.bookings.cancel', $booking) }}"
                onsubmit="return confirm('Batalkan booking ini? Jadwal akan dilepas.')">
                @csrf
                @method('PATCH')
                <label class="block text-xs font-medium text-slate-600 mb-1">Alasan pembatalan (opsional)</label>
                <textarea name="reason" rows="2"
                    class="w-full rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 text-xs mb-2"
                    placeholder="mis. Keadaan darurat medis keluarga..."></textarea>
                <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border border-rose-200 text-rose-600 font-semibold text-xs hover:bg-rose-50 transition-colors">
                    <x-lucide-x class="w-4 h-4" />
                    <span>Batalkan Booking</span>
                </button>
            </form>
        </div>
    @endif

    @if($showCheckIn || $showCheckOut)
        <!-- Check-in / check-out layanan (caregiver) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3">
            <div class="flex items-center gap-2">
                <x-lucide-scan class="w-4 h-4 text-rose-500" />
                <h3 class="text-xs font-bold text-slate-900">Absensi Layanan</h3>
            </div>
            
            @if($showCheckIn)
                <form method="POST" action="{{ route('caregiver.bookings.check-in', $booking) }}"
                    onsubmit="return confirm('Konfirmasi check-in pada waktu layanan ini?')">
                    @csrf
                    <p class="text-xs text-slate-500 mb-3">Konfirmasi bahwa Anda telah hadir di lokasi dan mulai mendampingi pasien.</p>
                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-emerald-600 text-white font-bold text-xs shadow-md shadow-emerald-200 hover:bg-emerald-700 transition-all">
                        <x-lucide-log-in class="w-4 h-4" />
                        <span>Check-in</span>
                    </button>
                </form>
            @endif

            @if($showCheckOut)
                <form method="POST" action="{{ route('caregiver.bookings.check-out', $booking) }}"
                    onsubmit="return confirm('Konfirmasi check-out? Layanan akan ditandai selesai.')">
                    @csrf
                    <p class="text-xs text-slate-500 mb-3">Konfirmasi bahwa pendampingan pasien telah selesai dengan aman.</p>
                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-rose-500 text-white font-bold text-xs shadow-md shadow-rose-200 hover:bg-rose-600 transition-all">
                        <x-lucide-log-out class="w-4 h-4" />
                        <span>Check-out</span>
                    </button>
                </form>
            @endif
        </div>
    @endif

    <!-- 7. Review & Rating (Customer) -->
    @if($isCustomer && $booking->status === \App\Models\Booking::STATUS_COMPLETED && $booking->reviews->where('visibility', \App\Models\Review::VISIBILITY_PUBLIC)->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3">
            <div class="flex items-center gap-2">
                <x-lucide-star class="w-4 h-4 text-amber-400 fill-current" />
                <h3 class="text-xs font-bold text-slate-900">Beri Ulasan Layanan Caregiver</h3>
            </div>
            <p class="text-xs text-slate-500">Bagikan pengalaman pendampingan Anda untuk membantu keluarga lainnya.</p>
            
            <form method="POST" action="{{ route('customer.bookings.review.store', $booking) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Rating Bintang</label>
                    <div class="flex gap-2">
                        @foreach([1, 2, 3, 4, 5] as $star)
                            <label class="cursor-pointer">
                                <input type="radio" name="rating" value="{{ $star }}" class="peer sr-only" @checked(old('rating', 5) == $star) required>
                                <x-lucide-star class="w-7 h-7 text-slate-200 peer-checked:text-amber-400 peer-checked:fill-current hover:text-amber-300 transition-colors" />
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label for="review-comment" class="block text-xs font-semibold text-slate-700 mb-1">Ulasan Anda (opsional)</label>
                    <textarea id="review-comment" name="comment" rows="3" maxlength="2000"
                        class="w-full rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 text-xs placeholder:text-slate-400"
                        placeholder="Ceritakan ketepatan waktu, keramahan, dan ketelitian caregiver...">{{ old('comment') }}</textarea>
                </div>
                <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-rose-500 text-white font-bold text-xs shadow-md shadow-rose-200 hover:bg-rose-600 transition-all">
                    <x-lucide-send class="w-4 h-4" />
                    <span>Kirim Ulasan</span>
                </button>
            </form>
        </div>
    @elseif($isCustomer && $booking->reviews->isNotEmpty())
        @php $myReview = $booking->reviews->firstWhere('visibility', \App\Models\Review::VISIBILITY_PUBLIC); @endphp
        @if($myReview)
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                        <x-lucide-star class="w-4 h-4 text-amber-400 fill-current" />
                        <span>Ulasan yang Anda Berikan</span>
                    </h3>
                    <div class="flex items-center gap-0.5">
                        @for($i = 1; $i <= 5; $i++)
                            <x-lucide-star class="{{ $i <= $myReview->rating ? 'text-amber-400 fill-current' : 'text-slate-200' }} w-3.5 h-3.5" />
                        @endfor
                    </div>
                </div>
                @if($myReview->comment)
                    <p class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100">{{ $myReview->comment }}</p>
                @endif
            </div>
        @endif
    @endif

    <!-- Catatan Privat Caregiver -->
    @if(! $isCustomer && $booking->status === \App\Models\Booking::STATUS_COMPLETED)
        @php $myNote = $booking->reviews->firstWhere('visibility', \App\Models\Review::VISIBILITY_PRIVATE); @endphp
        @if($myNote)
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                        <x-lucide-notebook-pen class="w-4 h-4 text-rose-500" />
                        <span>Catatan Privat Anda</span>
                    </h3>
                    <div class="flex items-center gap-0.5">
                        @for($i = 1; $i <= 5; $i++)
                            <x-lucide-star class="{{ $i <= $myNote->rating ? 'text-amber-400 fill-current' : 'text-slate-200' }} w-3.5 h-3.5" />
                        @endfor
                    </div>
                </div>
                <p class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100">{{ $myNote->comment }}</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3">
                <div class="flex items-center gap-2">
                    <x-lucide-notebook-pen class="w-4 h-4 text-rose-500" />
                    <h3 class="text-xs font-bold text-slate-900">Catatan Privat Layanan</h3>
                </div>
                <p class="text-xs text-slate-500">Catatan pengalaman Anda dengan pasien/keluarga. Hanya Anda yang dapat melihatnya.</p>
                <form method="POST" action="{{ route('caregiver.bookings.note.store', $booking) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Rating Pengalaman</label>
                        <div class="flex gap-2">
                            @foreach([1, 2, 3, 4, 5] as $star)
                                <label class="cursor-pointer">
                                    <input type="radio" name="rating" value="{{ $star }}" class="peer sr-only" @checked(old('rating', 5) == $star) required>
                                    <x-lucide-star class="w-7 h-7 text-slate-200 peer-checked:text-amber-400 peer-checked:fill-current hover:text-amber-300 transition-colors" />
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label for="caregiver-note" class="block text-xs font-semibold text-slate-700 mb-1">Catatan Anda</label>
                        <textarea id="caregiver-note" name="note" rows="3" maxlength="2000" required
                            class="w-full rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 text-xs placeholder:text-slate-400"
                            placeholder="Misal: Keluarga sangat ramah, lokasi mudah dijangkau...">{{ old('note') }}</textarea>
                    </div>
                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-rose-500 text-white font-bold text-xs shadow-md shadow-rose-200 hover:bg-rose-600 transition-all">
                        <x-lucide-save class="w-4 h-4" />
                        <span>Simpan Catatan Privat</span>
                    </button>
                </form>
            </div>
        @endif
    @endif

    <!-- 8. Chat Langsung -->
    @php $chatActive = in_array($booking->status, \App\Http\Controllers\BookingChatController::CHAT_ACTIVE_STATUSES, true); @endphp
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
        @include('booking._chat', ['chatActive' => $chatActive])
    </div>

    <!-- 9. Bantuan & Komplain (Halodoc Support) -->
    @php
        $openComplaint = $booking->complaints->firstWhere(fn ($c) => in_array($c->status, \App\Http\Controllers\ComplaintController::OPEN_STATUSES, true));
        $closedComplaint = $booking->complaints->whereNotIn('status', \App\Http\Controllers\ComplaintController::OPEN_STATUSES)->sortByDesc('updated_at')->first();
        $complaintRoute = $isCustomer ? 'customer.bookings.complaint.store' : 'caregiver.bookings.complaint.store';
    @endphp
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                <x-lucide-shield-alert class="w-4 h-4 text-rose-500" />
                <span>Bantuan & Kendala Layanan</span>
            </h3>
            <a href="{{ route('help') }}" class="text-[11px] font-semibold text-rose-500 hover:underline">
                Pusat Bantuan
            </a>
        </div>

        @if($openComplaint)
            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-amber-900"><span class="font-bold">Komplain aktif:</span> {{ $openComplaint->reason }}</p>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $openComplaint->status === 'open' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ $openComplaint->status === 'open' ? 'Baru' : 'Sedang Ditinjau' }}
                    </span>
                </div>
                @if($openComplaint->description)
                    <p class="text-amber-700 text-[11px] mt-1">{{ $openComplaint->description }}</p>
                @endif
            </div>
        @else
            @if($closedComplaint)
                <div class="p-3 rounded-xl border text-xs {{ $closedComplaint->status === 'resolved' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-50 text-slate-700' }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-bold">Komplain Selesai: {{ $closedComplaint->reason }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $closedComplaint->status === 'resolved' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                            {{ $closedComplaint->labelStatus() }}
                        </span>
                    </div>
                    @if($closedComplaint->resolution)
                        <p class="mt-1 text-[11px]"><span class="font-bold">Solusi:</span> {{ $closedComplaint->resolution }}</p>
                    @endif
                </div>
            @endif

            @if(in_array($booking->status, ['requested','accepted','confirmed','in_progress','completed'], true))
                <details class="group">
                    <summary class="cursor-pointer text-xs font-semibold text-rose-500 hover:text-rose-600 flex items-center gap-1.5 py-1">
                        <x-lucide-flag class="w-3.5 h-3.5" />
                        <span>{{ $closedComplaint ? 'Ajukan Komplain Baru' : 'Ajukan Komplain' }}</span>
                    </summary>
                    <form method="POST" action="{{ route($complaintRoute, $booking) }}" class="mt-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-2.5">
                        @csrf
                        <div>
                            <label for="complaint-reason" class="block text-xs font-semibold text-slate-700 mb-1">Alasan Komplain <span class="text-rose-500">*</span></label>
                            <input id="complaint-reason" name="reason" type="text" required maxlength="100"
                                class="w-full rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 text-xs"
                                placeholder="mis. Caregiver tidak hadir tepat waktu / layanan tidak sesuai..." />
                        </div>
                        <div>
                            <label for="complaint-description" class="block text-xs font-semibold text-slate-700 mb-1">Uraian Masalah</label>
                            <textarea id="complaint-description" name="description" rows="2" maxlength="2000"
                                class="w-full rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 text-xs"
                                placeholder="Jelaskan detail agar tim support dapat segera menginvestigasi..."></textarea>
                        </div>
                        <div class="flex items-center justify-between pt-1">
                            <a href="{{ route('help') }}" class="text-[11px] text-slate-500 flex items-center gap-1 hover:text-rose-600">
                                <x-lucide-phone-call class="w-3 h-3 text-rose-500" />
                                <span>Butuh bantuan darurat?</span>
                            </a>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500 text-white font-semibold text-xs hover:bg-rose-600 transition-colors shadow-sm shadow-rose-200">
                                Kirim Komplain
                            </button>
                        </div>
                    </form>
                </details>
            @endif
        @endif
    </div>

    <!-- 10. Riwayat Status Timeline -->
    @if($booking->statusHistories->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
            <h3 class="text-xs font-bold text-slate-900 mb-3 flex items-center gap-1.5">
                <x-lucide-history class="w-4 h-4 text-slate-400" />
                <span>Riwayat Perubahan Status</span>
            </h3>
            <div class="space-y-3 relative pl-3 border-l-2 border-slate-100 ml-2">
                @foreach($booking->statusHistories as $history)
                    <div class="relative">
                        <div class="absolute -left-[19px] top-1 w-2.5 h-2.5 rounded-full bg-rose-500 ring-4 ring-rose-50"></div>
                        <div class="text-xs">
                            <p class="font-bold text-slate-800">
                                {{ $history->labelTo() }}
                                @if($history->from_status)
                                    <span class="text-slate-400 text-[10px] font-normal">(dari {{ $history->labelFrom() }})</span>
                                @endif
                            </p>
                            <p class="text-[10px] text-slate-400 mt-0.5">
                                {{ $history->created_at->format('d M Y, H:i') }}
                                @if($history->actor)
                                    &middot; oleh {{ $history->actor->name }}
                                @endif
                            </p>
                            @if($history->note)
                                <p class="text-[11px] text-slate-600 bg-slate-50 p-2 rounded-lg mt-1 border border-slate-100">{{ $history->note }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>