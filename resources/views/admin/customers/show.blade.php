<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail Customer / Pasien') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Info Akun & Pasien -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-rose-100 to-rose-50 text-rose-600 flex items-center justify-center font-bold text-xl border border-rose-100 shrink-0">
                            {{ substr($customer->user->name, 0, 2) }}
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $customer->user->name }}</h3>
                            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-[10px] font-bold text-indigo-700">
                                    <x-lucide-id-card class="w-3 h-3" />
                                    {{ $customer->user->public_id }}
                                </span>
                                <span class="text-xs text-gray-500">{{ $customer->user->email }}</span>
                                @if($customer->user->phone)
                                    <span class="text-xs text-gray-500 flex items-center gap-1">
                                        <x-lucide-phone class="w-3 h-3" />
                                        {{ $customer->user->phone }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Terdaftar {{ $customer->user->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                    <a href="{{ url()->previous() }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-semibold text-gray-700 hover:bg-gray-50">
                        <x-lucide-arrow-left class="w-3.5 h-3.5 mr-1.5" />
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Info Pasien -->
        @php
            $patientName = $customer->patient_name ?: $customer->user->name;
            $gender = match($customer->patient_gender) { 'male' => 'Laki-laki', 'female' => 'Perempuan', default => null };
        @endphp
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2 mb-4">
                    <x-lucide-user-round class="w-4 h-4 text-rose-500" />
                    Data Pasien
                </h4>
                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 text-xs uppercase">Nama Pasien</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $patientName }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase">Jenis Kelamin</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $gender ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase">Umur</dt>
                        <dd class="mt-1 font-medium text-gray-900">
                            @if($customer->patient_birth_date)
                                {{ $customer->patient_birth_date->format('d M Y') }} ({{ $customer->patientAge() }} thn)
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase">Golongan Darah</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $customer->patient_blood_type ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase">Berat / Tinggi</dt>
                        <dd class="mt-1 font-medium text-gray-900">
                            @if($customer->patient_weight_kg || $customer->patient_height_cm)
                                {{ $customer->patient_weight_kg ? $customer->patient_weight_kg.' kg' : '-' }} / {{ $customer->patient_height_cm ? $customer->patient_height_cm.' cm' : '-' }}
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase">Alamat</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $customer->address ?? '-' }}</dd>
                    </div>
                </dl>

                @if($customer->patient_allergies)
                    <div class="mt-4 p-3 rounded-lg bg-amber-50 border border-amber-200">
                        <p class="text-xs font-bold text-amber-800 flex items-center gap-1.5">
                            <x-lucide-alert-triangle class="w-4 h-4" /> Alergi
                        </p>
                        <p class="text-sm text-amber-900 mt-0.5">{{ $customer->patient_allergies }}</p>
                    </div>
                @endif

                @if($customer->patient_condition)
                    <div class="mt-3 p-3 rounded-lg bg-slate-50 border border-slate-200">
                        <p class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                            <x-lucide-activity class="w-4 h-4" /> Kondisi / Riwayat Medis
                        </p>
                        <p class="text-sm text-gray-800 mt-0.5 whitespace-pre-line">{{ $customer->patient_condition }}</p>
                    </div>
                @endif

                @if($customer->patient_needs)
                    <div class="mt-3 p-3 rounded-lg bg-rose-50 border border-rose-200">
                        <p class="text-xs font-bold text-rose-700 flex items-center gap-1.5">
                            <x-lucide-heart-handshake class="w-4 h-4" /> Kebutuhan Pendampingan
                        </p>
                        <p class="text-sm text-rose-900 mt-0.5">{{ $customer->patient_needs }}</p>
                    </div>
                @endif

                @if($customer->emergency_contact || $customer->emergency_contact_phone)
                    <div class="mt-3 p-3 rounded-lg bg-emerald-50 border border-emerald-200">
                        <p class="text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                            <x-lucide-phone-call class="w-4 h-4" /> Kontak Darurat
                        </p>
                        <p class="text-sm text-emerald-900 mt-0.5">
                            {{ $customer->emergency_contact ?? '-' }}
                            @if($customer->emergency_contact_phone)
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $customer->emergency_contact_phone) }}" class="font-semibold hover:underline">
                                    · {{ $customer->emergency_contact_phone }}
                                </a>
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Riwayat Booking -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2 mb-4">
                    <x-lucide-calendar-days class="w-4 h-4 text-rose-500" />
                    Riwayat Booking
                </h4>
                @if($customer->bookings->isEmpty())
                    <p class="text-sm text-gray-400">Belum ada booking tercatat untuk customer ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Caregiver</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jadwal</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($customer->bookings as $booking)
                                    <tr>
                                        <td class="px-4 py-3 font-mono text-xs font-bold text-gray-900">{{ $booking->booking_code }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $booking->caregiver->user->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ $booking->start_time->format('d M Y, H:i') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $booking->label() }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
