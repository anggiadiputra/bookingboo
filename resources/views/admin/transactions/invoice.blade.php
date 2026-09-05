{{-- Invoice transaksi — tampilan finance/admin. Data: $payment (Payment) --}}
@php
    $booking = $payment->booking;
    $hours = (int) ceil($booking->start_time->diffInMinutes($booking->end_time) / 60);
    $rate = (float) ($booking->caregiver->hourly_rate ?? 0);
    $actual = $booking->actualMinutes();
    $billableHours = (int) ceil($booking->billableMinutes() / 60);
    $billableTotal = $booking->billableTotal();
    $platformFee = $booking->platformCommission();
    $caregiverNet = $booking->caregiverEarnings();
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Invoice Transaksi
        </h2>
    </x-slot>

    <div class="p-4">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('admin.transactions.index') }}"
                class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800">
                <x-lucide-arrow-left class="w-4 h-4 mr-1" />
                Kembali ke daftar transaksi
            </a>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border bg-gray-100 text-gray-700 border-gray-200 uppercase tracking-wider">
                Finance / Admin
            </span>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <!-- Header -->
                <div class="flex flex-wrap items-start justify-between gap-4 pb-5 border-b border-gray-200">
                    <div>
                        <div class="flex items-center gap-2">
                            <x-lucide-receipt class="w-6 h-6 text-indigo-600" />
                            <h3 class="text-xl font-bold text-gray-900">BookingBoo</h3>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Layanan pendampingan caregiver — dokumen finance</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Nomor Invoice</p>
                        <p class="text-lg font-bold text-gray-900">{{ $booking->invoiceNumber() }}</p>
                        <p class="mt-1 text-xs text-gray-500">Payment: {{ $payment->payment_code }}</p>
                        <p class="text-xs text-gray-500">Booking: {{ $booking->booking_code }}</p>
                        <p class="text-xs text-gray-500">Diterbitkan: {{ $payment->created_at?->format('d M Y H:i') }}</p>
                    </div>
                </div>

                <!-- Pihak -->
                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4 pb-5 border-b border-gray-200">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Customer (Ditagihkan)</p>
                        <p class="text-sm font-medium text-gray-900">{{ $booking->customer->user->name ?? '-' }}</p>
                        <p class="text-sm text-gray-500">{{ $booking->customer->user->email ?? '-' }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $booking->customer->phone ?? $booking->customer->user->phone ?? '' }}</p>
                    </div>
                    <div class="sm:text-right">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Layanan Oleh</p>
                        <p class="text-sm font-medium text-gray-900">{{ $booking->caregiver->user->name ?? '-' }}</p>
                        <p class="text-sm text-gray-500">{{ $booking->caregiver->service_area ?? '' }}</p>
                        <p class="text-xs text-gray-400 mt-1">ID: {{ $booking->caregiver->public_id ?? '' }}</p>
                    </div>
                </div>

                <!-- Rincian layanan -->
                <div class="mt-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Rincian Layanan</p>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs text-gray-500 uppercase tracking-wide">
                                <th class="py-2 font-medium">Layanan</th>
                                <th class="py-2 font-medium text-center">Durasi</th>
                                <th class="py-2 font-medium text-right">Tarif</th>
                                <th class="py-2 font-medium text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-100">
                                <td class="py-3">
                                    <p class="font-medium text-gray-900">
                                        Pendampingan caregiver - {{ $booking->start_time->format('d M Y') }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        @if($actual !== null)
                                            Aktual: {{ $booking->check_in_at?->format('H:i') }} - {{ $booking->check_out_at?->format('H:i') }}
                                            @if($booking->overtime_minutes > 0)
                                                &middot; Lembur {{ $booking->overtime_minutes }} menit
                                            @endif
                                        @else
                                            {{ $booking->start_time->format('H:i') }} - {{ $booking->end_time->format('H:i') }}
                                        @endif
                                        @if($booking->location)
                                            &middot; {{ $booking->location }}
                                        @endif
                                    </p>
                                </td>
                                <td class="py-3 text-center text-gray-900">{{ $billableHours }} jam</td>
                                <td class="py-3 text-right text-gray-900">Rp {{ number_format($rate, 0, ',', '.') }}</td>
                                <td class="py-3 text-right font-medium text-gray-900">Rp {{ number_format($billableTotal, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Total & rincian finance -->
                <div class="mt-4 flex justify-end">
                    <div class="w-full sm:w-80 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotal (tagihan)</span>
                            <span class="text-gray-900">Rp {{ number_format($billableTotal, 0, ',', '.') }}</span>
                        </div>
                        @if($payment->refunded_amount > 0)
                            <div class="flex justify-between text-red-600">
                                <span>Refund</span>
                                <span>- Rp {{ number_format($payment->refunded_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if($payment->platform_fee > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Komisi platform ({{ (int) config('booking.commission.platform_percent') }}%)</span>
                                <span class="text-gray-900">Rp {{ number_format($payment->platform_fee, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between pt-2 border-t border-gray-200 text-base font-bold">
                            <span class="text-gray-900">Total</span>
                            <span class="text-indigo-600">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Rincian pendapatan caregiver -->
                <div class="mt-5 pt-5 border-t border-gray-200">
                    <div class="flex items-start gap-3 rounded-md border border-indigo-100 bg-indigo-50/60 px-4 py-3">
                        <x-lucide-coins class="w-5 h-5 text-indigo-500 mt-0.5" />
                        <div class="text-sm">
                            <p class="font-medium text-gray-900">Rincian Keuangan (finance/admin)</p>
                            <p class="text-gray-700 mt-1">
                                Nilai tagihan: <span class="font-medium">Rp {{ number_format($billableTotal, 0, ',', '.') }}</span>
                                &middot; Komisi platform: <span class="font-medium">Rp {{ number_format($platformFee, 0, ',', '.') }}</span>
                                &middot; Pendapatan caregiver: <span class="font-medium text-indigo-700">Rp {{ number_format($caregiverNet, 0, ',', '.') }}</span>
                            </p>
                            @if($payment->paid_at)
                                <p class="text-xs text-gray-500 mt-2">
                                    Dibayar: {{ $payment->paid_at->format('d M Y, H:i') }}
                                    @if($payment->method) &middot; Metode: <span class="uppercase">{{ $payment->method }}</span> @endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Status pembayaran -->
                <div class="mt-5 pt-5 border-t border-gray-200">
                    <div class="flex items-center gap-2 text-sm">
                        <x-lucide-wallet class="w-4 h-4 text-gray-400" />
                        @if($payment->status === 'paid')
                            <span class="text-green-700 font-medium">Lunas &middot; {{ $payment->payment_code }}</span>
                        @elseif($payment->status === 'pending')
                            <span class="text-amber-700 font-medium">Menunggu pembayaran &middot; {{ $payment->payment_code }}</span>
                        @elseif($payment->status === 'refunded')
                            <span class="text-red-700 font-medium">Telah direfund &middot; {{ $payment->payment_code }}</span>
                        @elseif($payment->status === 'failed')
                            <span class="text-red-700 font-medium">Pembayaran gagal &middot; {{ $payment->payment_code }}</span>
                        @else
                            <span class="text-gray-500">Status: {{ $payment->status }}</span>
                        @endif
                    </div>
                    @if($booking->customer)
                        <div class="mt-3 flex gap-3">
                            <a href="{{ route('admin.customers.show', $booking->customer) }}"
                                class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                <x-lucide-user class="w-4 h-4" /> Lihat Customer
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
