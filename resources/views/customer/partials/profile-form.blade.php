@props(['customer', 'patientName'])

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-sm font-bold text-gray-900">Edit Profil Pasien</h3>
            <p class="text-xs text-gray-500 mt-0.5">Lengkapi data pasien agar caregiver dapat mempersiapkan layanan terbaik.</p>
        </div>
        <button type="button" onclick="document.getElementById('patient-edit').classList.add('hidden'); document.getElementById('patient-summary').classList.remove('hidden')"
                class="text-xs font-semibold text-gray-500 hover:text-gray-800 inline-flex items-center gap-1">
            <x-lucide-x class="w-4 h-4" /> Tutup
        </button>
    </div>

    <form method="post" action="{{ route('customer.profile.update') }}" class="space-y-5" id="patient-form" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <!-- Foto Profil -->
        <fieldset>
            <legend class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Foto Profil</legend>
            <div class="flex flex-col items-center gap-3">
                <div class="w-28 h-28 rounded-full overflow-hidden bg-gradient-to-tr from-rose-100 to-rose-50 border-4 border-white shadow-lg ring-1 ring-rose-100 flex items-center justify-center text-rose-600 font-extrabold text-2xl">
                    @if($customer->photo)
                        <img src="{{ asset('storage/' . $customer->photo) }}" alt="Foto profil pasien" class="w-full h-full object-cover">
                    @else
                        {{ collect(explode(' ', trim($patientName)))->filter()->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->implode('') ?: 'PB' }}
                    @endif
                </div>
                <div class="w-full max-w-sm">
                    <x-input-label for="photo" :value="__('Unggah Foto Baru')" />
                    <input id="photo" name="photo" type="file" accept="image/*"
                        class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-rose-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-rose-700 hover:file:bg-rose-100">
                    <p class="mt-1 text-[11px] text-slate-500">JPG, PNG, atau WebP. Maks 2MB.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('photo')" />
                </div>
            </div>
        </fieldset>

        <!-- 1. Identitas Pasien -->
        <fieldset>
            <legend class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Identitas Pasien</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-input-label for="patient_name" :value="__('Nama Pasien')" />
                    <x-text-input id="patient_name" name="patient_name" type="text" class="mt-1 block w-full"
                        :value="old('patient_name', $customer->patient_name ?? $patientName)" placeholder="Nama lengkap pasien" />
                    <x-input-error class="mt-2" :messages="$errors->get('patient_name')" />
                </div>

                <div>
                    <x-input-label for="patient_birth_date" :value="__('Tanggal Lahir')" />
                    <x-text-input id="patient_birth_date" name="patient_birth_date" type="date" class="mt-1 block w-full"
                        :value="old('patient_birth_date', $customer->patient_birth_date?->format('Y-m-d'))" />
                    <x-input-error class="mt-2" :messages="$errors->get('patient_birth_date')" />
                </div>

                <div>
                    <x-input-label for="patient_gender" :value="__('Jenis Kelamin')" />
                    <select id="patient_gender" name="patient_gender"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">-- Pilih --</option>
                        <option value="male" @selected(old('patient_gender', $customer->patient_gender) === 'male')>Laki-laki</option>
                        <option value="female" @selected(old('patient_gender', $customer->patient_gender) === 'female')>Perempuan</option>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('patient_gender')" />
                </div>

                <div>
                    <x-input-label for="patient_blood_type" :value="__('Golongan Darah')" />
                    <select id="patient_blood_type" name="patient_blood_type"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">-- Pilih --</option>
                        @foreach (['A', 'B', 'AB', 'O'] as $blood)
                            <option value="{{ $blood }}" @selected(old('patient_blood_type', $customer->patient_blood_type) === $blood)>{{ $blood }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('patient_blood_type')" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="patient_weight_kg" :value="__('Berat (kg)')" />
                        <x-text-input id="patient_weight_kg" name="patient_weight_kg" type="number" step="0.1" min="0.5" max="400" class="mt-1 block w-full"
                            :value="old('patient_weight_kg', $customer->patient_weight_kg)" placeholder="60" />
                        <x-input-error class="mt-2" :messages="$errors->get('patient_weight_kg')" />
                    </div>
                    <div>
                        <x-input-label for="patient_height_cm" :value="__('Tinggi (cm)')" />
                        <x-text-input id="patient_height_cm" name="patient_height_cm" type="number" step="0.1" min="30" max="250" class="mt-1 block w-full"
                            :value="old('patient_height_cm', $customer->patient_height_cm)" placeholder="165" />
                        <x-input-error class="mt-2" :messages="$errors->get('patient_height_cm')" />
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- 2. Data Medis -->
        <fieldset class="pt-1">
            <legend class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Data Medis & Alergi</legend>
            <div class="space-y-4">
                <div>
                    <x-input-label for="patient_allergies" :value="__('Alergi')" />
                    <x-text-input id="patient_allergies" name="patient_allergies" type="text" class="mt-1 block w-full"
                        :value="old('patient_allergies', $customer->patient_allergies)" placeholder="cth: Penisilin, seafood — kosongkan jika tidak ada" />
                    <x-input-error class="mt-2" :messages="$errors->get('patient_allergies')" />
                </div>
                <div>
                    <x-input-label for="patient_condition" :value="__('Kondisi / Riwayat Medis')" />
                    <textarea id="patient_condition" name="patient_condition" rows="3"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        placeholder="cth: Hipertensi, diabetes tipe 2, riwayat stroke 2023...">{{ old('patient_condition', $customer->patient_condition) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('patient_condition')" />
                </div>
            </div>
        </fieldset>

        <!-- 3. Alamat & Kebutuhan -->
        <fieldset class="pt-1">
            <legend class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Alamat & Kebutuhan Pendampingan</legend>
            <div class="space-y-4">
                <div>
                    <x-input-label for="address" :value="__('Alamat')" />
                    <x-text-input id="address" name="address" type="text" class="mt-1 block w-full"
                        :value="old('address', $customer->address)" placeholder="cth: Jl. Melati No. 12, Jakarta" />
                    <x-input-error class="mt-2" :messages="$errors->get('address')" />
                </div>
                <div>
                    <x-input-label for="patient_needs" :value="__('Kebutuhan Pasien')" />
                    <textarea id="patient_needs" name="patient_needs" rows="3"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        placeholder="cth: Pendampingan kontrol dokter, perawatan luka, bantuan mobilitas">{{ old('patient_needs', $customer->patient_needs) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('patient_needs')" />
                </div>
            </div>
        </fieldset>

        <!-- 4. Kontak Darurat -->
        <fieldset class="pt-1">
            <legend class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Kontak Darurat</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="emergency_contact" :value="__('Nama Kontak Darurat')" />
                    <x-text-input id="emergency_contact" name="emergency_contact" type="text" class="mt-1 block w-full"
                        :value="old('emergency_contact', $customer->emergency_contact)" placeholder="cth: Siti Rahayu (Istri)" />
                    <x-input-error class="mt-2" :messages="$errors->get('emergency_contact')" />
                </div>
                <div>
                    <x-input-label for="emergency_contact_phone" :value="__('No. HP Kontak Darurat')" />
                    <x-text-input id="emergency_contact_phone" name="emergency_contact_phone" type="text" class="mt-1 block w-full"
                        :value="old('emergency_contact_phone', $customer->emergency_contact_phone)" placeholder="cth: 081234567890" />
                    <x-input-error class="mt-2" :messages="$errors->get('emergency_contact_phone')" />
                </div>
            </div>
        </fieldset>

        <!-- Aksi -->
        <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-black text-white text-xs font-semibold rounded-xl hover:bg-gray-800 transition-colors shadow-sm">
                <x-lucide-save class="w-4 h-4" />
                Simpan Profil Pasien
            </button>
            <button type="button" onclick="document.getElementById('patient-edit').classList.add('hidden'); document.getElementById('patient-summary').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                Batal
            </button>
        </div>
    </form>
</div>
