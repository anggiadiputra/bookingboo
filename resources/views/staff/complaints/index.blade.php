<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel Komplain') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <div class="flex items-center">
                            <x-lucide-flag class="w-5 h-5 text-indigo-600 mr-2" />
                            <h3 class="text-lg font-bold text-gray-900">Daftar Komplain</h3>
                            @if($openCount > 0)
                                <span class="ml-3 text-xs px-2 py-1 rounded-full bg-red-100 text-red-700">{{ $openCount }} aktif</span>
                            @endif
                        </div>
                        <div class="flex gap-2 text-sm">
                            <a href="{{ route('complaints.staff.index') }}"
                                class="px-3 py-1.5 rounded-md border {{ $status ? 'border-gray-300 text-gray-600 hover:bg-gray-50' : 'border-indigo-300 bg-indigo-50 text-indigo-700 font-medium' }}">
                                Semua
                            </a>
                            @foreach(['open' => 'Baru', 'in_review' => 'Ditinjau', 'resolved' => 'Selesai', 'rejected' => 'Ditolak'] as $s => $label)
                                <a href="{{ route('complaints.staff.index', ['status' => $s]) }}"
                                    class="px-3 py-1.5 rounded-md border {{ $status === $s ? 'border-indigo-300 bg-indigo-50 text-indigo-700 font-medium' : 'border-gray-300 text-gray-600 hover:bg-gray-50' }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    @if($complaints->isEmpty())
                        <p class="text-sm text-gray-500 py-8 text-center">Belum ada komplain.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-gray-500 uppercase">
                                        <th class="py-2 pr-4">Booking</th>
                                        <th class="py-2 pr-4">Reporter</th>
                                        <th class="py-2 pr-4">Alasan</th>
                                        <th class="py-2 pr-4">Status</th>
                                        <th class="py-2 pr-4">Masuk</th>
                                        <th class="py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($complaints as $complaint)
                                        <tr>
                                            <td class="py-3 pr-4 font-medium text-gray-900">{{ $complaint->booking->booking_code }}</td>
                                            <td class="py-3 pr-4 text-gray-700">{{ $complaint->reporter?->name ?? '-' }}</td>
                                            <td class="py-3 pr-4 text-gray-700 max-w-xs truncate">{{ $complaint->reason }}</td>
                                            <td class="py-3 pr-4">
                                                @php $cls = match($complaint->status) {
                                                    'open' => 'bg-yellow-100 text-yellow-800',
                                                    'in_review' => 'bg-blue-100 text-blue-800',
                                                    'resolved' => 'bg-green-100 text-green-800',
                                                    default => 'bg-red-100 text-red-700',
                                                }; @endphp
                                                <span class="px-2 py-1 rounded-full text-xs {{ $cls }}">{{ $complaint->labelStatus() }}</span>
                                            </td>
                                            <td class="py-3 pr-4 text-gray-500 text-xs">{{ $complaint->created_at->format('d M Y, H:i') }}</td>
                                            <td class="py-3">
                                                <a href="{{ route('complaints.staff.show', $complaint) }}"
                                                    class="text-indigo-600 hover:text-indigo-800 font-medium">Tinjau</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $complaints->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
</x-app-layout>