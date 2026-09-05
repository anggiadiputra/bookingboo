<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pengajuan Pencairan Caregiver') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-md">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-md">
                {{ $errors->first() }}
            </div>
        @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <x-lucide-coins class="w-6 h-6 text-yellow-500" />
                        <h3 class="text-lg font-medium text-gray-900">Daftar Pengajuan</h3>
                    </div>

                    @if ($payouts->isEmpty())
                        <p class="text-gray-500">Belum ada pengajuan pencairan.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Caregiver</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catatan</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($payouts as $payout)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $payout->created_at->format('d M Y H:i') }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $payout->caregiver->user->name }}</td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">Rp {{ number_format($payout->amount, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $payout->note ?? '—' }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                                    {{ $payout->status === \App\Models\Payout::STATUS_PENDING ? 'bg-yellow-100 text-yellow-700' : '' }}
                                                    {{ $payout->status === \App\Models\Payout::STATUS_PAID ? 'bg-green-100 text-green-700' : '' }}
                                                    {{ $payout->status === \App\Models\Payout::STATUS_REJECTED ? 'bg-red-100 text-red-700' : '' }}">
                                                    {{ $payout->label() }}
                                                </span>
                                                @if ($payout->processed_at)
                                                    <p class="text-xs text-gray-400 mt-1">
                                                        {{ $payout->processed_at->format('d M Y H:i') }} · {{ $payout->processor?->name ?? 'Admin' }}
                                                    </p>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right">
                                                @if ($payout->status === \App\Models\Payout::STATUS_PENDING)
                                                    <form method="POST" action="{{ route('admin.payouts.process', $payout) }}" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="action" value="pay" />
                                                        <button type="submit" onclick="return confirm('Tandai pencairan Rp {{ number_format($payout->amount, 0, ',', '.') }} sebagai dibayar?')"
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700">
                                                            <x-lucide-check class="w-4 h-4" /> Bayar
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.payouts.process', $payout) }}" class="inline ml-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="action" value="reject" />
                                                        <button type="submit" onclick="return confirm('Tolak pengajuan pencairan ini?')"
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-md hover:bg-red-700">
                                                            <x-lucide-x class="w-4 h-4" /> Tolak
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-400 text-xs">Diproses</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $payouts->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
</x-app-layout>