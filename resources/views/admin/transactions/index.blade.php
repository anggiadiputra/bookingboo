<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Transaksi') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-md">
                {{ session('status') }}
            </div>
        @endif

        <!-- Ringkasan -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <x-lucide-receipt class="w-4 h-4 text-gray-700" />
                    Total Transaksi
                </div>
                <p class="text-2xl font-black text-gray-900 mt-2">{{ number_format($summary['all']) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <x-lucide-credit-card class="w-4 h-4 text-emerald-600" />
                    Total Lunas
                </div>
                <p class="text-2xl font-black text-emerald-700 mt-2">Rp {{ number_format($summary['paid'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <x-lucide-hourglass class="w-4 h-4 text-amber-600" />
                    Menunggu
                </div>
                <p class="text-2xl font-black text-amber-700 mt-2">{{ number_format($summary['pending']) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <x-lucide-rotate-ccw class="w-4 h-4 text-red-600" />
                    Total Refund
                </div>
                <p class="text-2xl font-black text-red-700 mt-2">Rp {{ number_format($summary['refunded'], 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <form method="GET" action="{{ route('admin.transactions.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Cari</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Kode payment / booking / nama"
                        class="w-64 rounded-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Status</label>
                    <select name="status" class="rounded-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Semua status</option>
                        @foreach (['pending' => 'Pending', 'paid' => 'Lunas', 'refunded' => 'Refund', 'failed' => 'Gagal'] as $val => $label)
                            <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Dari</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="rounded-md border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Sampai</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="rounded-md border-gray-300 text-sm" />
                </div>
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-black text-white text-xs font-semibold rounded-md hover:bg-gray-800">
                    <x-lucide-filter class="w-3.5 h-3.5" /> Filter
                </button>
                @if(request()->hasAny(['q', 'status', 'from', 'to']))
                    <a href="{{ route('admin.transactions.index') }}" class="text-xs font-semibold text-gray-500 hover:text-black">Reset</a>
                @endif
            </form>
        </div>

        <!-- Tabel -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Payment</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metode</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($payments as $payment)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                                    {{ $payment->created_at?->format('d M Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-xs font-mono font-bold text-gray-900">
                                    {{ $payment->payment_code }}
                                </td>
                                <td class="px-4 py-3 text-xs font-mono text-gray-600">
                                    {{ $payment->booking->booking_code ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-700">
                                    {{ $payment->booking->customer->user->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs font-bold text-gray-900 whitespace-nowrap">
                                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-xs uppercase text-gray-500">
                                    {{ $payment->method ?: ($payment->payment_type ?: 'Online') }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        {{ $payment->status === \App\Models\Payment::STATUS_PAID ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $payment->status === \App\Models\Payment::STATUS_PENDING ? 'bg-amber-100 text-amber-700' : '' }}
                                        {{ $payment->status === \App\Models\Payment::STATUS_REFUNDED ? 'bg-red-100 text-red-700' : '' }}
                                        {{ $payment->status === \App\Models\Payment::STATUS_FAILED ? 'bg-gray-100 text-gray-600' : '' }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if($payment->booking)
                                        <a href="{{ route('admin.transactions.invoice', $payment) }}"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                            <x-lucide-file-text class="w-3.5 h-3.5" /> Invoice
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-gray-400 text-xs">
                                    Tidak ada transaksi yang cocok dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $payments->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
