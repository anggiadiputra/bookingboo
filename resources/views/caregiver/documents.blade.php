<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dokumen Verifikasi') }}
        </h2>
    </x-slot>

    <div class="p-4">
            @if (session('status'))
                <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Status verifikasi -->
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Status Verifikasi</h3>
                            <p class="text-sm text-gray-500 mt-1">Unggah dokumen untuk diverifikasi admin sebelum dapat menerima booking.</p>
                        </div>
                        @if ($caregiver->isVerified())
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <x-lucide-badge-check class="w-4 h-4 mr-1" />
                                Terverifikasi
                            </span>
                        @elseif ($caregiver->verification_status === 'rejected')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <x-lucide-x-circle class="w-4 h-4 mr-1" />
                                Ditolak
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                <x-lucide-clock class="w-4 h-4 mr-1" />
                                Menunggu
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Form upload -->
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Unggah Dokumen</h3>
                    <form method="POST" action="{{ route('caregiver.documents.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <x-input-label for="type" :value="__('Jenis Dokumen')" />
                            <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach (\App\Models\CaregiverDocument::TYPES as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>
                        <div class="mb-4">
                            <x-input-label for="document" :value="__('File Dokumen')" />
                            <x-text-input id="document" class="block mt-1 w-full" type="file" name="document" accept=".jpg,.jpeg,.png,.pdf" />
                            <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, atau PDF. Maksimal 5 MB.</p>
                            <x-input-error :messages="$errors->get('document')" class="mt-2" />
                        </div>
                        <div class="flex items-center justify-end">
                            <x-primary-button>
                                <x-lucide-upload class="w-4 h-4 mr-2" />
                                {{ __('Unggah Dokumen') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Daftar dokumen -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Dokumen Terunggah</h3>
                    @forelse ($documents as $document)
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                            <div class="flex items-center">
                                <x-lucide-file-text class="w-5 h-5 text-gray-400 mr-3" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ \App\Models\CaregiverDocument::TYPES[$document->type] ?? $document->type }}</p>
                                    <p class="text-xs text-gray-500">{{ $document->created_at->format('d M Y H:i') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                @if ($document->status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <x-lucide-check-circle class="w-3 h-3 mr-1" />
                                        Disetujui
                                    </span>
                                @elseif ($document->status === 'rejected')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <x-lucide-x-circle class="w-3 h-3 mr-1" />
                                        Ditolak
                                    </span>
                                    @if ($document->rejection_reason)
                                        <span class="text-xs text-red-600" title="{{ $document->rejection_reason }}">Alasan: {{ $document->rejection_reason }}</span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <x-lucide-clock class="w-3 h-3 mr-1" />
                                        Menunggu
                                    </span>
                                @endif
                                <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                    <x-lucide-eye class="w-4 h-4" />
                                </a>
                                @if ($document->status === 'pending')
                                    <form method="POST" action="{{ route('caregiver.documents.destroy', $document) }}" onsubmit="return confirm('Hapus dokumen ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">
                                            <x-lucide-trash-2 class="w-4 h-4" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada dokumen yang diunggah.</p>
                    @endforelse
                </div>
            </div>
        </div>
</x-app-layout>
