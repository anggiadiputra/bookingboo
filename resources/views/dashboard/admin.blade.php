<x-app-layout :title="__('Dashboard Admin')">
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-gray-200">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl font-bold text-gray-900 tracking-tight">Dashboard Admin</h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-black text-white border-black">
                        admin
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    Selamat datang kembali, <span class="font-semibold text-gray-800">{{ auth()->user()->name }}</span>! Pantau performa platform dan kelola operasional BookingBoo.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <div class="inline-flex items-center px-3.5 py-2 bg-white border border-gray-200 rounded-lg shadow-sm">
                    <x-lucide-id-card class="w-4 h-4 text-gray-400 mr-2 shrink-0" />
                    <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">ID Staf:</span>
                    <span class="ml-1.5 text-xs font-bold text-gray-900 font-mono">{{ auth()->user()->public_id }}</span>
                </div>
                <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-black text-white text-xs font-semibold rounded-lg hover:bg-gray-800 transition-colors shadow-sm">
                    <x-lucide-history class="w-3.5 h-3.5" />
                    <span>Audit Log</span>
                </a>
            </div>
        </div>

        @php
            $complaintOpen = $stats['complaintCounts']['open'] + $stats['complaintCounts']['in_review'];
        @endphp

        <!-- 1. Ringkasan Pengguna -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <x-lucide-users class="w-4 h-4 text-gray-800" />
                    <span>Pengguna Terdaftar</span>
                </h2>
                <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-gray-600 hover:text-black flex items-center gap-1">
                    Kelola Semua <x-lucide-arrow-right class="w-3 h-3" />
                </a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-users class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Total Pengguna</p>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($stats['userCounts']['total']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-heart-handshake class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Pasien/Keluarga</p>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($stats['userCounts']['customers']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-stethoscope class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Caregiver</p>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($stats['userCounts']['caregivers']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-emerald-700 rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-badge-check class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Terverifikasi</p>
                        <p class="text-2xl font-black text-emerald-700 mt-0.5">{{ number_format($stats['userCounts']['caregivers_verified']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-shield class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Staf Platform</p>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($stats['userCounts']['staff']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Ringkasan Booking & Operasional -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <x-lucide-calendar-days class="w-4 h-4 text-gray-800" />
                    <span>Aktivitas Booking Layanan</span>
                </h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-calendar class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Total Booking</p>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($stats['bookingCounts']['total']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-blue-700 rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-clock class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Aktif Berjalan</p>
                        <p class="text-2xl font-black text-blue-700 mt-0.5">{{ number_format($stats['bookingCounts']['active']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-emerald-700 rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-check-circle-2 class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Selesai</p>
                        <p class="text-2xl font-black text-emerald-700 mt-0.5">{{ number_format($stats['bookingCounts']['completed']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-red-700 rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-x-circle class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Batal / Ditolak</p>
                        <p class="text-2xl font-black text-red-700 mt-0.5">{{ number_format($stats['bookingCounts']['cancelled']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Ringkasan Finansial Platform -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <x-lucide-credit-card class="w-4 h-4 text-gray-800" />
                    <span>Pembayaran & Keuangan</span>
                </h2>
                <a href="{{ route('finance.dashboard') }}" class="text-xs font-semibold text-gray-600 hover:text-black flex items-center gap-1">
                    Buka Dashboard Finance <x-lucide-arrow-right class="w-3 h-3" />
                </a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-receipt class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Pembayaran Lunas</p>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($stats['paymentTotals']['paid_count']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-coins class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Total Transaksi</p>
                        <p class="text-xl font-black text-gray-900 mt-0.5">Rp {{ number_format($stats['paymentTotals']['paid_amount'], 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-emerald-700 rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-trending-up class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Pendapatan Platform</p>
                        <p class="text-xl font-black text-emerald-700 mt-0.5">Rp {{ number_format($stats['paymentTotals']['platform_revenue'], 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-red-700 rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-rotate-ccw class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Total Refund</p>
                        <p class="text-xl font-black text-red-700 mt-0.5">Rp {{ number_format($stats['paymentTotals']['refunded_amount'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Ringkasan Komplain & Layanan Pelanggan -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <x-lucide-flag class="w-4 h-4 text-gray-800" />
                    <span>Status Komplain & Sengketa</span>
                </h2>
                <a href="{{ route('support.dashboard') }}" class="text-xs font-semibold text-gray-600 hover:text-black flex items-center gap-1">
                    Buka Dashboard CS <x-lucide-arrow-right class="w-3 h-3" />
                </a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 {{ $stats['complaintCounts']['open'] > 0 ? 'bg-amber-600' : 'bg-black' }} rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-alert-circle class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Baru</p>
                        <p class="text-2xl font-black {{ $stats['complaintCounts']['open'] > 0 ? 'text-amber-700' : 'text-gray-900' }} mt-0.5">{{ number_format($stats['complaintCounts']['open']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 {{ $stats['complaintCounts']['in_review'] > 0 ? 'bg-blue-600' : 'bg-black' }} rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-search class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Ditinjau</p>
                        <p class="text-2xl font-black {{ $stats['complaintCounts']['in_review'] > 0 ? 'text-blue-700' : 'text-gray-900' }} mt-0.5">{{ number_format($stats['complaintCounts']['in_review']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-emerald-700 rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-check class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Selesai</p>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($stats['complaintCounts']['resolved']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-x class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Ditolak</p>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($stats['complaintCounts']['rejected']) }}</p>
                    </div>
                </div>

                <a href="{{ route('complaints.staff.index') }}" class="bg-black text-white rounded-2xl shadow-sm border border-black p-5 flex items-center gap-4 hover:bg-gray-800 transition-colors">
                    <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center shrink-0 text-white">
                        <x-lucide-flag class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-300 tracking-wider">Perlu Tindak Lanjut</p>
                        <p class="text-2xl font-black text-white mt-0.5">{{ $complaintOpen }}</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- 5. Tabel Booking Terbaru (design.md Table 6f) -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Aktivitas Booking Terbaru</h3>
                    <p class="text-xs text-gray-500 mt-0.5">5 pemesanan layanan paling mutakhir</p>
                </div>
                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Realtime</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Caregiver</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($stats['recentBookings'] as $booking)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 text-xs font-mono font-bold text-gray-900">
                                    {{ $booking->booking_code }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-800">
                                    {{ $booking->customer->user->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-800">
                                    {{ $booking->caregiver->user->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @php
                                        $badgeClass = match($booking->status) {
                                            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-150',
                                            'in_progress', 'confirmed' => 'bg-blue-50 text-blue-700 border-blue-100',
                                            'requested', 'accepted' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            default => 'bg-red-50 text-red-700 border-red-100',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }} uppercase">
                                        {{ $booking->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-right text-gray-500">
                                    {{ $booking->created_at?->format('d M Y, H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-xs">
                                    Belum ada transaksi pemesanan tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 6. Aksi Cepat & Navigasi Manajemen -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <x-lucide-layout-grid class="w-4 h-4 text-gray-800" />
                    <span>Pintasan Operasional & Moderasi</span>
                </h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="{{ route('admin.staff.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-users class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Manajemen Staf</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Kelola akun staf, persetujuan dan hak akses</p>
                    </div>
                </a>

                <a href="{{ route('admin.caregivers.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-badge-check class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Verifikasi Caregiver</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Tinjau STR, KTP, dan sertifikasi caregiver</p>
                    </div>
                </a>

                <a href="{{ route('admin.bookings.replacement.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-refresh-ccw class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Caregiver Pengganti</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Tugaskan pengganti untuk order terbatalkan</p>
                    </div>
                </a>

                <a href="{{ route('admin.payouts.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-coins class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Pencairan Dana (Payout)</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Kelola antrean pencairan saldo caregiver</p>
                    </div>
                </a>

                <a href="{{ route('complaints.staff.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-flag class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Panel Komplain</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Tangani tiket keluhan dan investigasi sengketa</p>
                    </div>
                </a>

                <a href="{{ route('admin.reviews.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-message-square class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Moderasi Review</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Saring ulasan dan penilaian pelanggan</p>
                    </div>
                </a>

                <a href="{{ route('admin.users.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-shield-off class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Moderasi Akun</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Suspend atau aktifkan kembali akun</p>
                    </div>
                </a>

                <a href="{{ route('admin.audit-logs.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-history class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Audit Log</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Rekam jejak seluruh mutasi dan aksi admin</p>
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
