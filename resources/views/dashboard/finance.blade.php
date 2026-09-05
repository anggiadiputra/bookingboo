<x-app-layout :title="__('Dashboard Finance')">
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-gray-200">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl font-bold text-gray-900 tracking-tight">Dashboard Finance</h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-gray-100 text-gray-700 border-gray-200">
                        finance
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    Selamat datang kembali, <span class="font-semibold text-gray-800">{{ auth()->user()->name }}</span>! Kelola transaksi pembayaran, pencairan dana mitra caregiver, dan refund.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <div class="inline-flex items-center px-3.5 py-2 bg-white border border-gray-200 rounded-lg shadow-sm">
                    <x-lucide-id-card class="w-4 h-4 text-gray-400 mr-2 shrink-0" />
                    <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">ID Staf:</span>
                    <span class="ml-1.5 text-xs font-bold text-gray-900 font-mono">{{ auth()->user()->public_id }}</span>
                </div>
                <a href="{{ route('admin.payouts.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-black text-white text-xs font-semibold rounded-lg hover:bg-gray-800 transition-colors shadow-sm">
                    <x-lucide-coins class="w-3.5 h-3.5" />
                    <span>Kelola Payout Penuh</span>
                </a>
            </div>
        </div>

        <!-- 1. Ringkasan Payout (Pencairan Dana) -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <x-lucide-hand-coins class="w-4 h-4 text-gray-800" />
                    <span>Pencairan Dana (Payout Mitra)</span>
                </h2>
                <a href="{{ route('admin.payouts.index') }}" class="text-xs font-semibold text-gray-600 hover:text-black flex items-center gap-1">
                    Buka Antrean Payout <x-lucide-arrow-right class="w-3 h-3" />
                </a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 {{ $payoutStatuses['pending'] > 0 ? 'bg-amber-600' : 'bg-black' }} rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-timer class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Menunggu Proses</p>
                        <p class="text-2xl font-black {{ $payoutStatuses['pending'] > 0 ? 'text-amber-700' : 'text-gray-900' }} mt-0.5">{{ number_format($payoutStatuses['pending']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-coins class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Nilai Antrean</p>
                        <p class="text-xl font-black text-gray-900 mt-0.5">Rp {{ number_format($payoutTotals['pending_amount'], 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-emerald-700 rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-check-circle-2 class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Sudah Dibayar</p>
                        <p class="text-2xl font-black text-emerald-700 mt-0.5">{{ number_format($payoutStatuses['paid']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-wallet class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Total Tercairkan</p>
                        <p class="text-xl font-black text-gray-900 mt-0.5">Rp {{ number_format($payoutTotals['paid_amount'], 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-x-circle class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Ditolak</p>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($payoutStatuses['rejected']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Ringkasan Pembayaran & Refund -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <x-lucide-credit-card class="w-4 h-4 text-gray-800" />
                    <span>Pembayaran Masuk & Pengembalian Dana</span>
                </h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center justify-between hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Pembayaran Pending</p>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($paymentTotals['pending_count']) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Nilai antrean: Rp {{ number_format($paymentTotals['pending_amount'], 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0 text-white">
                        <x-lucide-hourglass class="w-5 h-5" />
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center justify-between hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Total Refund</p>
                        <p class="text-2xl font-black text-red-700 mt-0.5">Rp {{ number_format($disputeAmounts['refunded'], 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 mt-1">Pengembalian dana sengketa / pembatalan</p>
                    </div>
                    <div class="w-12 h-12 bg-red-700 rounded-xl flex items-center justify-center shrink-0 text-white">
                        <x-lucide-rotate-ccw class="w-5 h-5" />
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Tabel Antrean Payout (design.md Table 6f) -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Antrean Payout Menunggu Proses</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Permintaan penarikan saldo oleh mitra caregiver</p>
                </div>
                <a href="{{ route('admin.payouts.index') }}" class="text-xs font-semibold text-gray-600 hover:text-black">
                    Lihat Semua Antrean →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Caregiver</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah Penarikan</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Catatan</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Diajukan Pada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($payoutQueue as $payout)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 text-xs font-semibold text-gray-900">
                                    {{ $payout->caregiver->user->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs font-bold text-gray-900">
                                    Rp {{ number_format($payout->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ $payout->note ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-right text-gray-500">
                                    {{ $payout->created_at?->format('d M Y, H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-xs">
                                    Tidak ada pengajuan menunggu proses saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Dua Kolom: Pembayaran Menunggu & Pembayaran Lunas Terbaru -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Pembayaran Menunggu -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Pembayaran Menunggu</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Transaksi checkout belum diselesaikan</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode Booking</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nilai Tagihan</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu Dibuat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($pendingPayments as $payment)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3 text-xs font-mono font-bold text-gray-900">
                                        {{ $payment->booking->booking_code ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-900">
                                        Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-right text-gray-500">
                                        {{ $payment->created_at?->format('d M Y H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-gray-400 text-xs">
                                        Tidak ada pembayaran menunggu.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pembayaran Lunas Terbaru -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Pembayaran Lunas Terbaru</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Dana berhasil masuk via payment gateway</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode Booking</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nilai Lunas</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Metode</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu Bayar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($recentPaid as $payment)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3 text-xs font-mono font-bold text-gray-900">
                                        {{ $payment->booking->booking_code ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-900">
                                        Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600 uppercase font-medium">
                                        {{ $payment->method ?? 'Online' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-right text-gray-500">
                                        {{ $payment->paid_at?->format('d M Y H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-400 text-xs">
                                        Belum ada pembayaran lunas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 5. Menu Tugas & Navigasi -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <x-lucide-layout-grid class="w-4 h-4 text-gray-800" />
                    <span>Menu Tindakan & Navigasi Finansial</span>
                </h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('admin.payouts.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-coins class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Proses Payout</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Setujui atau tolak pengajuan pencairan caregiver</p>
                    </div>
                </a>

                <a href="{{ route('complaints.staff.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-scale class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Resolusi Sengketa</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Catat refund atau tahan payout terkait komplain</p>
                    </div>
                </a>

                <a href="{{ route('admin.transactions.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-receipt class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Daftar Transaksi</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Semua pembayaran & invoice per transaksi</p>
                    </div>
                </a>

                <a href="{{ route('help') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-life-buoy class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Bantuan & Darurat</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Kontak darurat platform dan pusat bantuan</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
