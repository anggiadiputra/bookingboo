<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tugaskan Caregiver Pengganti') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
                {{ $errors->first() }}
            </div>
        @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <x-lucide-refresh-ccw class="w-5 h-5 text-indigo-600 mr-2" />
                            <h3 class="text-lg font-medium text-gray-900">{{ $booking->booking_code }}</h3>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <x-lucide-alert-circle class="w-3 h-3 mr-1" />
                            Menunggu Pengganti
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Customer</p>
                            <p class="font-medium text-gray-900">{{ $booking->customer->user->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Caregiver Lama</p>
                            <p class="font-medium text-gray-900">{{ $booking->caregiver->user->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Jadwal</p>
                            <p class="font-medium text-gray-900">
                                {{ $booking->start_time->translatedFormat('d M Y, H:i') }} - {{ $booking->end_time->format('H:i') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-500">Alasan Pembatalan</p>
                            <p class="font-medium text-gray-900">{{ $booking->cancellation_reason ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Kandidat Caregiver Pengganti</h3>
                    <p class="text-sm text-gray-500 mb-6">Hanya caregiver terverifikasi dengan jadwal yang mencakup seluruh rentang booking dan tidak bentrok booking lain.</p>

                    <div class="space-y-3">
                        @forelse ($candidates as $candidate)
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $candidate->user->name }}</p>
                                    <p class="text-sm text-gray-500">
                                        Rp {{ number_format($candidate->hourly_rate, 0, ',', '.') }}/jam
                                        @if($candidate->service_area)
                                            · {{ $candidate->service_area }}
                                        @endif
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('admin.bookings.replacement.assign', $booking) }}"
                                    onsubmit="return confirm('Tugaskan {{ $candidate->user->name }} sebagai caregiver pengganti?')">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="caregiver_id" value="{{ $candidate->id }}" />
                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none transition ease-in-out duration-150">
                                        <x-lucide-user-check class="w-4 h-4 mr-2" />
                                        Tugaskan
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="flex flex-col items-center gap-2 py-6 text-sm text-gray-500">
                                <x-lucide-search-x class="w-8 h-8 text-gray-300" />
                                Tidak ada kandidat caregiver yang tersedia pada rentang waktu ini.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
</x-app-layout>