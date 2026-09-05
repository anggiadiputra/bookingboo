<x-app-layout :title="__('Dashboard Customer Service')">
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-gray-200">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl font-bold text-gray-900 tracking-tight">Dashboard Customer Service</h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-gray-100 text-gray-700 border-gray-200">
                        support
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    Selamat datang kembali, <span class="font-semibold text-gray-800">{{ auth()->user()->name }}</span>! Tangani keluhan pelanggan, selesaikan sengketa pemesanan, dan moderasi ulasan.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <div class="inline-flex items-center px-3.5 py-2 bg-white border border-gray-200 rounded-lg shadow-sm">
                    <x-lucide-id-card class="w-4 h-4 text-gray-400 mr-2 shrink-0" />
                    <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">ID Staf:</span>
                    <span class="ml-1.5 text-xs font-bold text-gray-900 font-mono">{{ auth()->user()->public_id }}</span>
                </div>
                <a href="{{ route('complaints.staff.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-black text-white text-xs font-semibold rounded-lg hover:bg-gray-800 transition-colors shadow-sm">
                    <x-lucide-flag class="w-3.5 h-3.5" />
                    <span>Panel Komplain Lengkap</span>
                </a>
            </div>
        </div>

        @php
            $complaintOpen = $complaintCounts['open'] + $complaintCounts['in_review'];
        @endphp

        <!-- 1. Ringkasan Status Komplain -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <x-lucide-flag class="w-4 h-4 text-gray-800" />
                    <span>Status Tiket Komplain</span>
                </h2>
                <a href="{{ route('complaints.staff.index') }}" class="text-xs font-semibold text-gray-600 hover:text-black flex items-center gap-1">
                    Kelola Seluruh Tiket <x-lucide-arrow-right class="w-3 h-3" />
                </a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 {{ $complaintCounts['open'] > 0 ? 'bg-amber-600' : 'bg-black' }} rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-alert-circle class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Baru</p>
                        <p class="text-2xl font-black {{ $complaintCounts['open'] > 0 ? 'text-amber-700' : 'text-gray-900' }} mt-0.5">{{ number_format($complaintCounts['open']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 {{ $complaintCounts['in_review'] > 0 ? 'bg-blue-600' : 'bg-black' }} rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-search class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Ditinjau</p>
                        <p class="text-2xl font-black {{ $complaintCounts['in_review'] > 0 ? 'text-blue-700' : 'text-gray-900' }} mt-0.5">{{ number_format($complaintCounts['in_review']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-emerald-700 rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-check-circle-2 class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Selesai</p>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($complaintCounts['resolved']) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0">
                        <x-lucide-x-circle class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Ditolak</p>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($complaintCounts['rejected']) }}</p>
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

        <!-- 2. Ringkasan Kinerja & Moderasi Hari Ini -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <x-lucide-activity class="w-4 h-4 text-gray-800" />
                    <span>Aktivitas Dukungan & Pelanggan</span>
                </h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center justify-between hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Komplain Dituntaskan Hari Ini</p>
                        <p class="text-2xl font-black text-emerald-700 mt-0.5">{{ number_format($handledToday) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Penyelesaian tiket selesai atau ditolak</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-700 rounded-xl flex items-center justify-center shrink-0 text-white">
                        <x-lucide-check-check class="w-5 h-5" />
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center justify-between hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Review Terbaru (5 Terakhir)</p>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($recentReviews->count()) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Ulasan masuk siap dimoderasi</p>
                    </div>
                    <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center shrink-0 text-white">
                        <x-lucide-message-square class="w-5 h-5" />
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Tabel Antrean Komplain (design.md Table 6f) -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Antrean Komplain (Baru & Ditinjau)</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Tiket kendala layanan yang memerlukan tindakan staf</p>
                </div>
                <a href="{{ route('complaints.staff.index') }}" class="text-xs font-semibold text-gray-600 hover:text-black">
                    Buka Panel Komplain →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Alasan Masalah</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelapor</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode Booking</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Dilaporkan Pada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($queue as $complaint)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 text-xs font-semibold text-gray-900">
                                    {{ $complaint->reason }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-800">
                                    {{ $complaint->reporter->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs font-mono font-bold text-gray-900">
                                    {{ $complaint->booking->booking_code ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $complaint->status === 'open' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-100' }} uppercase">
                                        {{ $complaint->labelStatus() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-right text-gray-500">
                                    {{ $complaint->created_at?->format('d M Y, H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-xs">
                                    Antrean kosong — tidak ada komplain menunggu.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Review Terbaru Pasien (design.md Table 6f) -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Review Terbaru</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Penilaian dan umpan balik pengguna atas layanan caregiver</p>
                </div>
                <a href="{{ route('admin.reviews.index') }}" class="text-xs font-semibold text-gray-600 hover:text-black">
                    Moderasi Semua Review →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Rating</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ulasan / Komentar</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode Booking</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentReviews as $review)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-amber-50 text-amber-700 border-amber-200">
                                        ★ {{ $review->rating }}/5
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-700 max-w-md">
                                    <p class="italic text-gray-600">"{{ $review->comment ?? '-' }}"</p>
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-xs font-semibold text-gray-500">
                                    {{ $review->booking->booking_code ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-400 text-xs">
                                    Belum ada review.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 5. Menu Tugas Staf Dukungan -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <x-lucide-layout-grid class="w-4 h-4 text-gray-800" />
                    <span>Menu Tugas & Layanan Bantuan</span>
                </h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('complaints.staff.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-flag class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Panel Komplain</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Tinjau dan tindaklanjuti komplain booking</p>
                    </div>
                </a>

                <a href="{{ route('admin.reviews.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-message-square class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Moderasi Review</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Sembunyikan review yang melanggar ketentuan</p>
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

                <a href="{{ route('admin.audit-logs.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-start gap-3.5 group">
                    <div class="w-10 h-10 bg-gray-100 group-hover:bg-black group-hover:text-white rounded-lg flex items-center justify-center shrink-0 transition-colors text-gray-800">
                        <x-lucide-history class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide group-hover:text-black">Audit Log</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Riwayat aktivitas penting platform</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
