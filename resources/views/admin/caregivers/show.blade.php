<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tinjau Caregiver') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                {{ session('status') }}
            </div>
        @endif

            <!-- Info caregiver -->
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            @if ($caregiver->photo)
                                <img src="{{ asset('storage/' . $caregiver->photo) }}" alt="Foto" class="w-16 h-16 rounded-full object-cover mr-4">
                            @else
                                <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                    <x-lucide-user class="w-8 h-8 text-gray-400" />
                                </div>
                            @endif
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ $caregiver->user->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $caregiver->user->public_id }} · {{ $caregiver->user->email }}</p>
                                <p class="text-sm text-gray-500 mt-1">Tarif: Rp {{ number_format($caregiver->hourly_rate, 0, ',', '.') }}/jam · Area: {{ $caregiver->service_area ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
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

                    @if ($caregiver->bio)
                        <p class="mt-4 text-sm text-gray-600">{{ $caregiver->bio }}</p>
                    @endif

                    <div class="mt-6 flex items-center gap-3">
                        @if (! $caregiver->isVerified())
                            <form method="POST" action="{{ route('admin.caregivers.approve', $caregiver) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none transition ease-in-out duration-150">
                                    <x-lucide-check class="w-4 h-4 mr-1" />
                                    Setujui Caregiver
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.caregivers.reject', $caregiver) }}" onsubmit="return confirm('Tolak caregiver ini?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none transition ease-in-out duration-150">
                                    <x-lucide-x class="w-4 h-4 mr-1" />
                                    Tolak Caregiver
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.caregivers.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none transition ease-in-out duration-150">
                            Kembali
                        </a>
                    </div>
                </div>
            </div>

            <!-- Dokumen -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Dokumen Verifikasi</h3>
                    @forelse ($caregiver->documents as $document)
                        <div class="py-4 border-b border-gray-100 last:border-0">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <x-lucide-file-text class="w-5 h-5 text-gray-400 mr-3" />
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ \App\Models\CaregiverDocument::TYPES[$document->type] ?? $document->type }}</p>
                                        <p class="text-xs text-gray-500">Diunggah {{ $document->created_at->format('d M Y H:i') }}</p>
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
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <x-lucide-clock class="w-3 h-3 mr-1" />
                                            Menunggu
                                        </span>
                                    @endif
                                    <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 text-sm">
                                        <x-lucide-eye class="w-4 h-4 mr-1" />
                                        Lihat
                                    </a>
                                </div>
                            </div>

                            @if ($document->rejection_reason)
                                <p class="mt-2 text-sm text-red-600">Alasan penolakan: {{ $document->rejection_reason }}</p>
                            @endif

                            @if ($document->status === 'pending')
                                <div class="mt-3 flex items-center gap-3">
                                    <form method="POST" action="{{ route('admin.caregivers.documents.approve', $document) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none transition ease-in-out duration-150">
                                            <x-lucide-check class="w-3 h-3 mr-1" />
                                            Setujui
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.caregivers.documents.reject', $document) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="rejection_reason" placeholder="Alasan penolakan" required class="block w-64 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none transition ease-in-out duration-150">
                                            <x-lucide-x class="w-3 h-3 mr-1" />
                                            Tolak
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada dokumen yang diunggah caregiver ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
</x-app-layout>
