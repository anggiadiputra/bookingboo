<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pencairan Dana') }}
        </h2>
    </x-slot>

    <div class="p-4">
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

            <!-- Ringkasan dana -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <x-lucide-wallet class="w-6 h-6 text-blue-600" />
                        <h3 class="text-lg font-medium text-gray-900">Ringkasan Pendapatan</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-md">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Dana Dapat Dicairkan</p>
                            <p class="text-2xl font-bold text-blue-700 mt-1">Rp {{ number_format($eligible, 0, ',', '.') }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-md">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Tarif per Jam</p>
                            <p class="text-2xl font-bold text-gray-700 mt-1">Rp {{ number_format($caregiver->hourly_rate, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form pengajuan pencairan -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <x-lucide-banknote class="w-6 h-6 text-green-600" />
                        <h3 class="text-lg font-medium text-gray-900">Ajukan Pencairan</h3>
                    </div>

                    @if ($hasPending)
                        <p class="text-gray-500">Masih ada pengajuan yang menunggu diproses admin. Mohon tunggu.</p>
                    @elseif ($eligible <= 0)
                        <p class="text-gray-500">Belum ada dana yang dapat dicairkan. Selesaikan booking yang sudah dibayar terlebih dahulu.</p>
                    @else
                        <form method="POST" action="{{ route('caregiver.payouts.store') }}">
                            @csrf
                            <div>
                                <x-input-label for="note" :value="__('Catatan (opsional)')" />
                                <x-text-input id="note" class="block mt-1 w-full" type="text" name="note" placeholder="Contoh: Transfer ke BCA ****1234" />
                                <x-input-error :messages="$errors->get('note')" class="mt-2" />
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <p class="text-sm text-gray-500">Jumlah dicairkan: <strong>Rp {{ number_format($eligible, 0, ',', '.') }}</strong></p>
                                <x-primary-button>
                                    {{ __('Ajukan Pencairan') }}
                                </x-primary-button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Riwayat pengajuan -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Riwayat Pengajuan</h3>

                    @if ($payouts->isEmpty())
                        <p class="text-gray-500">Belum ada pengajuan pencairan.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catatan Admin</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($payouts as $payout)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $payout->created_at->format('d M Y H:i') }}</td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">Rp {{ number_format($payout->amount, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                                    {{ $payout->status === \App\Models\Payout::STATUS_PENDING ? 'bg-yellow-100 text-yellow-700' : '' }}
                                                    {{ $payout->status === \App\Models\Payout::STATUS_PAID ? 'bg-green-100 text-green-700' : '' }}
                                                    {{ $payout->status === \App\Models\Payout::STATUS_REJECTED ? 'bg-red-100 text-red-700' : '' }}">
                                                    {{ $payout->label() }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $payout->admin_note ?? '—' }}</td>
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