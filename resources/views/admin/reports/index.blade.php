<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan Transaksi & Performa') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Filter periode -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-gray-900">Laporan Platform</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}
                </p>
            </div>
            <div class="flex items-center gap-1.5">
                @foreach (['30d' => '30 hari', '90d' => '90 hari', '12m' => '12 bulan', 'all' => 'Semua'] as $key => $label)
                    <a href="{{ route('admin.reports.index', ['period' => $key]) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $period === $key ? 'bg-black text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Kartu ringkasan -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <x-lucide-credit-card class="w-4 h-4 text-emerald-600" />
                    Pembayaran Lunas
                </div>
                <p class="text-2xl font-black text-gray-900 mt-2">Rp {{ number_format($paidAmount, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $paymentCounts['paid'] }} transaksi sukses</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <x-lucide-trending-up class="w-4 h-4 text-blue-600" />
                    Pendapatan Platform
                </div>
                <p class="text-2xl font-black text-gray-900 mt-2">Rp {{ number_format($platformRevenue, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $bookingCounts['completed'] }} booking selesai</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <x-lucide-rotate-ccw class="w-4 h-4 text-red-600" />
                    Total Refund
                </div>
                <p class="text-2xl font-black text-red-700 mt-2">Rp {{ number_format($refundedAmount, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $paymentCounts['refunded'] }} transaksi refund</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <x-lucide-wallet class="w-4 h-4 text-amber-600" />
                    Payout Caregiver
                </div>
                <p class="text-2xl font-black text-gray-900 mt-2">Rp {{ number_format($payoutCounts['paid_amount'], 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">Pending: Rp {{ number_format($payoutCounts['pending_amount'], 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Top caregiver & pengguna baru -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Top Caregiver Periode Ini</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Berdasarkan jumlah booking selesai</p>
                </div>
                @if($topCaregivers->isEmpty())
                    <p class="text-sm text-gray-400 text-center py-8">Belum ada data pada periode ini.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Caregiver</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Booking Selesai</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($topCaregivers as $cg)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $cg['name'] }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-800">{{ $cg['bookings'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-800">Rp {{ number_format($cg['revenue'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Lainnya</h3>
                <div class="mt-4 space-y-4 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Pengguna baru</span>
                        <span class="font-bold text-gray-900">{{ number_format($newUsers) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Total booking</span>
                        <span class="font-bold text-gray-900">{{ number_format($bookingCounts['total']) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Booking dibatalkan</span>
                        <span class="font-bold text-red-600">{{ number_format($bookingCounts['cancelled']) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Pembayaran pending</span>
                        <span class="font-bold text-amber-600">{{ number_format($paymentCounts['pending']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rincian transaksi terbaru -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Transaksi Terbaru (50)</h3>
            </div>
            @if($payments->isEmpty())
                <p class="text-sm text-gray-400 text-center py-8">Belum ada transaksi pada periode ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Kode</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Booking</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Customer</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Metode</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Nilai</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($payments as $payment)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs font-bold text-gray-900">{{ $payment->payment_code }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ $payment->booking->booking_code ?? '-' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-800">{{ $payment->booking->customer->user->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-xs uppercase text-gray-500">{{ $payment->method ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right text-xs font-semibold text-gray-900">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @php
                                            $cls = match($payment->status) {
                                                'paid' => 'bg-emerald-50 text-emerald-700',
                                                'pending' => 'bg-amber-50 text-amber-700',
                                                'refunded' => 'bg-red-50 text-red-700',
                                                default => 'bg-gray-100 text-gray-600',
                                            };
                                        @endphp
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $cls }}">{{ $payment->status }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
