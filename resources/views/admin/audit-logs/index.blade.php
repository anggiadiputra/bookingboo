<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
            <x-lucide-history class="w-5 h-5 mr-2 text-indigo-600" />
            Audit Log
        </h2>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-500 mb-4">
                        Riwayat aktivitas penting platform: autentikasi, persetujuan staf, verifikasi caregiver,
                        pembayaran, pencairan dana, refund, dan moderasi.
                    </p>

                    {{-- Filter --}}
                    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="mb-4 flex flex-wrap items-center gap-2">
                        <div class="relative flex-1 min-w-[220px]">
                            <x-lucide-search class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            <input type="text" name="q" value="{{ $search }}"
                                placeholder="Cari deskripsi, aksi, atau aktor..."
                                class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <select name="action"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua aksi</option>
                            @foreach ($actions as $value => $label)
                                <option value="{{ $value }}" {{ $action === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="actor"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua aktor</option>
                            @foreach ($actors as $actor)
                                <option value="{{ $actor->id }}" {{ (string) $actorId === (string) $actor->id ? 'selected' : '' }}>
                                    {{ $actor->name }} ({{ $actor->public_id }})
                                </option>
                            @endforeach
                        </select>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 flex items-center">
                            <x-lucide-filter class="w-4 h-4 mr-1" /> Filter
                        </button>
                        <a href="{{ route('admin.audit-logs.index') }}"
                            class="px-3 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50 flex items-center">
                            <x-lucide-rotate-ccw class="w-4 h-4 mr-1" /> Reset
                        </a>
                    </form>

                    {{-- Tabel log --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Waktu</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Aksi</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Aktor</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Subjek</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Deskripsi</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($logs as $log)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 whitespace-nowrap text-gray-600">
                                            {{ $log->created_at->format('d M Y, H:i') }}
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            @php($prefix = strstr($log->action, '.', true))
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                {{ $prefix === 'auth' ? 'bg-blue-50 text-blue-700' : '' }}
                                                {{ in_array($prefix, ['payment', 'refund', 'payout']) ? 'bg-yellow-50 text-yellow-700' : '' }}
                                                {{ in_array($prefix, ['review', 'user']) ? 'bg-red-50 text-red-700' : '' }}
                                                {{ in_array($prefix, ['staff', 'caregiver']) ? 'bg-green-50 text-green-700' : '' }}
                                                {{ $prefix === 'booking' ? 'bg-purple-50 text-purple-700' : '' }}
                                                {{ $prefix === 'dispute' ? 'bg-orange-50 text-orange-700' : '' }}
                                                {{ ! in_array($prefix, ['auth', 'payment', 'refund', 'payout', 'review', 'user', 'staff', 'caregiver', 'booking', 'dispute']) ? 'bg-gray-50 text-gray-700' : '' }}">
                                                {{ $log->actionLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            @if ($log->user)
                                                {{ $log->user->name }}
                                                <span class="text-xs text-gray-400">({{ $log->user->public_id }})</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600">
                                                    <x-lucide-bot class="w-3 h-3 mr-1" /> Sistem
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-600">{{ $log->subjectLabel() }}</td>
                                        <td class="px-3 py-2 text-gray-700">{{ $log->description ?? '-' }}</td>
                                        <td class="px-3 py-2 text-xs text-gray-500">
                                            @if ($log->properties)
                                                <details>
                                                    <summary class="cursor-pointer text-indigo-600">Metadata</summary>
                                                    <pre class="mt-1 bg-gray-50 rounded p-2 max-w-xs overflow-x-auto">@json($log->properties)</pre>
                                                </details>
                                            @else
                                                -
                                            @endif
                                            @if ($log->ip_address)
                                                <span class="block text-gray-400">IP: {{ $log->ip_address }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-8 text-center text-gray-400">
                                            Belum ada aktivitas tercatat.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
</x-app-layout>