<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profil Caregiver') }}
        </h2>
    </x-slot>

    @php $hasErrors = $errors->any(); @endphp

    <div class="p-4">
        @if (session('status'))
            <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-md">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-5 inline-flex items-center px-3 py-1.5 bg-indigo-50 border border-indigo-200 rounded-md">
            <x-lucide-id-card class="w-4 h-4 text-indigo-600 mr-2" />
            <span class="text-sm text-gray-600">ID Caregiver:</span>
            <span class="ml-2 text-sm font-semibold text-indigo-700">{{ auth()->user()->public_id }}</span>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-slate-100"
            x-data="{ editing: {{ $hasErrors ? 'true' : 'false' }} }">
            <div class="p-6">
                <form method="POST" action="{{ route('caregiver.profile.update') }}" enctype="multipart/form-data"
                    x-on:submit="!editing && $event.preventDefault()">
                    @csrf
                    @method('PATCH')

                    <!-- Foto -->
                    <div class="mb-6">
                        <x-input-label for="photo" :value="__('Foto Profil')" />
                        @if ($caregiver->photo)
                            <div class="mt-2 mb-2">
                                <img src="{{ asset('storage/' . $caregiver->photo) }}" alt="Foto profil" class="w-24 h-24 rounded-full object-cover">
                            </div>
                        @endif
                        <x-text-input id="photo" class="block mt-1 w-full" type="file" name="photo" accept="image/*"
                            x-on:change="editing = true" />
                        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                    </div>

                    <!-- Keahlian -->
                    <div class="mb-6">
                        <x-input-label for="skills" :value="__('Keahlian')" />
                        <textarea id="skills" name="skills" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Contoh: Perawatan lansia, pendampingan kontrol, perawatan luka"
                            readonly
                            x-bind:readonly="!editing"
                            x-bind:class="!editing ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : ''">{{ old('skills', $caregiver->skills) }}</textarea>
                        <x-input-error :messages="$errors->get('skills')" class="mt-2" />
                    </div>

                    <!-- Area Layanan -->
                    <div class="mb-6">
                        <x-input-label for="service_area" :value="__('Area Layanan')" />
                        <x-text-input id="service_area" class="block mt-1 w-full" type="text" name="service_area" :value="old('service_area', $caregiver->service_area)" placeholder="Contoh: Jakarta Selatan, Jakarta Pusat"
                            readonly
                            x-bind:readonly="!editing"
                            x-bind:class="!editing ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : ''" />
                        <x-input-error :messages="$errors->get('service_area')" class="mt-2" />
                    </div>

                    <!-- Tarif per Jam -->
                    <div class="mb-6">
                        <x-input-label for="hourly_rate" :value="__('Tarif per Jam (Rp)')" />
                        <x-text-input id="hourly_rate" class="block mt-1 w-full" type="number" name="hourly_rate" :value="old('hourly_rate', $caregiver->hourly_rate)" min="0" step="1000"
                            readonly
                            x-bind:readonly="!editing"
                            x-bind:class="!editing ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : ''" />
                        <x-input-error :messages="$errors->get('hourly_rate')" class="mt-2" />
                    </div>

                    <!-- Bio -->
                    <div class="mb-6">
                        <x-input-label for="bio" :value="__('Bio / Deskripsi')" />
                        <textarea id="bio" name="bio" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Ceritakan pengalaman dan keahlian Anda"
                            readonly
                            x-bind:readonly="!editing"
                            x-bind:class="!editing ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : ''">{{ old('bio', $caregiver->bio) }}</textarea>
                        <x-input-error :messages="$errors->get('bio')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-3">
                        <x-secondary-button type="button" x-show="!editing" x-cloak x-on:click="editing = true">
                            {{ __('Ubah Profil') }}
                        </x-secondary-button>

                        <div class="flex items-center gap-3" x-show="editing" x-cloak>
                            <x-primary-button type="submit">{{ __('Simpan Profil') }}</x-primary-button>
                            <x-secondary-button type="button" x-on:click="editing = false; $root.querySelector('form').reset()">{{ __('Batal') }}</x-secondary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
