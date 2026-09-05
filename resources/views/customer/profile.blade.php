<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profil Pasien / Keluarga') }}
        </h2>
    </x-slot>

    @php
        $hasErrors = $errors->any();
        $patientName = $customer->patient_name ?: auth()->user()->name;
        $patientAge = $customer->patientAge();
        $genderLabel = match($customer->patient_gender) {
            'male' => 'Laki-laki',
            'female' => 'Perempuan',
            default => null,
        };
    @endphp

    <div class="p-4 space-y-4">
        @if (session('status'))
            <div class="px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- ===== Kartu Info Ringkas Pasien ===== -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 {{ $hasErrors ? 'hidden' : '' }}" id="patient-summary">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-rose-100 to-rose-50 text-rose-600 flex items-center justify-center font-bold text-xl border border-rose-100 shrink-0">
                    {{ substr($patientName, 0, 2) }}
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-bold text-slate-900 truncate">{{ $patientName }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        @if($genderLabel)
                            {{ $genderLabel }}
                        @endif
                        @if($patientAge)
                            {{ $genderLabel ? '·' : '' }} {{ $patientAge }} tahun
                        @endif
                    </p>
                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-[10px] font-bold text-indigo-700">
                            <x-lucide-id-card class="w-3 h-3" />
                            {{ auth()->user()->public_id }}
                        </span>
                        @if($customer->patient_blood_type)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-rose-50 border border-rose-200 text-[10px] font-bold text-rose-700">
                                Darah {{ $customer->patient_blood_type }}
                            </span>
                        @endif
                        @if($customer->patient_weight_kg || $customer->patient_height_cm)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-[10px] font-bold text-emerald-700">
                                @if($customer->patient_weight_kg){{ $customer->patient_weight_kg }} kg @endif
                                @if($customer->patient_weight_kg && $customer->patient_height_cm) · @endif
                                @if($customer->patient_height_cm){{ $customer->patient_height_cm }} cm @endif
                            </span>
                        @endif
                    </div>
                </div>
                <div class="shrink-0">
                    <button type="button" onclick="document.getElementById('patient-edit').classList.remove('hidden'); document.getElementById('patient-summary').classList.add('hidden'); window.scrollTo({top:0,behavior:'smooth'})"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-black text-white text-xs font-semibold rounded-xl hover:bg-gray-800 transition-colors">
                        <x-lucide-pencil class="w-4 h-4" />
                        Ubah Profil
                    </button>
                </div>
            </div>

            @if($customer->patient_condition)
                <div class="mt-4 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Kondisi / Riwayat Medis</p>
                    <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">{{ $customer->patient_condition }}</p>
                </div>
            @endif

            @if($customer->patient_allergies)
                <div class="mt-2 p-3.5 rounded-xl bg-amber-50/70 border border-amber-100">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-600 mb-1 flex items-center gap-1">
                        <x-lucide-alert-triangle class="w-3 h-3" /> Alergi
                    </p>
                    <p class="text-xs text-amber-800 leading-relaxed">{{ $customer->patient_allergies }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4 pt-4 border-t border-slate-100">
                @if($customer->address)
                    <div class="flex items-start gap-2.5 text-xs">
                        <x-lucide-map-pin class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-[10px] text-slate-400 font-medium">Alamat</p>
                            <p class="font-medium text-slate-800 mt-0.5">{{ $customer->address }}</p>
                        </div>
                    </div>
                @endif
                @if($customer->emergency_contact || $customer->emergency_contact_phone)
                    <div class="flex items-start gap-2.5 text-xs">
                        <x-lucide-phone-call class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-[10px] text-slate-400 font-medium">Kontak Darurat</p>
                            <p class="font-medium text-slate-800 mt-0.5">
                                {{ $customer->emergency_contact }}
                                @if($customer->emergency_contact_phone)
                                    <span class="text-slate-400">·</span>
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $customer->emergency_contact_phone) }}" class="text-rose-600 hover:underline">
                                        {{ $customer->emergency_contact_phone }}
                                    </a>
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            @if($customer->patient_needs)
                <p class="mt-3 text-xs text-rose-700 bg-rose-50/70 border border-rose-100 rounded-xl p-3 leading-relaxed">
                    <span class="font-bold">Kebutuhan pendampingan:</span> {{ $customer->patient_needs }}
                </p>
            @endif
        </div>

        <!-- ===== Form Edit (tersembunyi sampai diklik) ===== -->
        <div id="patient-edit" class="{{ $hasErrors ? '' : 'hidden' }}">
            @include('customer.partials.profile-form', ['customer' => $customer, 'patientName' => $patientName])
        </div>

        <!-- ===== Keluar ===== -->
        <form method="POST" action="{{ route('logout') }}" class="pt-2">
            @csrf
            <button type="submit" onclick="return confirm('Yakin ingin keluar dari akun?')"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-2xl border border-rose-200 bg-rose-50/60 text-rose-600 text-sm font-semibold hover:bg-rose-100 transition-colors">
                <x-lucide-log-out class="w-4 h-4" />
                Keluar dari Akun
            </button>
        </form>
    </div>
</x-app-layout>
