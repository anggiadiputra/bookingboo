<!-- Invoice booking, dipakai customer & caregiver. $booking, $viewer (customer|caregiver) -->
@php
    $isCustomer = $viewer === 'customer';
    $hours = (int) ceil($booking->start_time->diffInMinutes($booking->end_time) / 60);
    $rate = (float) ($booking->caregiver->hourly_rate ?? 0);
    $payment = $booking->latestPayment();
    $actual = $booking->actualMinutes();
    $billableHours = (int) ceil($booking->billableMinutes() / 60);
    $billableTotal = $booking->billableTotal();
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Invoice
        </h2>
    </x-slot>

    <div class="p-4">
            <div class="mb-4 flex items-center justify-between">
                <a href="{{ $isCustomer ? route('customer.bookings.show', $booking) : route('caregiver.bookings.show', $booking) }}"
                    class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800">
                    <x-lucide-arrow-left class="w-4 h-4 mr-1" />
                    Kembali ke detail booking
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Header invoice -->
                    <div class="flex flex-wrap items-start justify-between gap-4 pb-5 border-b border-gray-200">
                        <div>
                            <div class="flex items-center gap-2">
                                <x-lucide-receipt class="w-6 h-6 text-indigo-600" />
                                <h3 class="text-xl font-bold text-gray-900">BookingBoo</h3>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Layanan pendampingan caregiver</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Nomor Invoice</p>
                            <p class="text-lg font-bold text-gray-900">{{ $booking->invoiceNumber() }}</p>
                            <p class="mt-1 text-xs text-gray-500">Booking: {{ $booking->booking_code }}</p>
                            <p class="text-xs text-gray-500">Diterbitkan: {{ now()->format('d M Y') }}</p>
                        </div>
                    </div>

                    <!-- Pihak -->
                    <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4 pb-5 border-b border-gray-200">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Ditagihkan Kepada</p>
                            <p class="text-sm font-medium text-gray-900">{{ $booking->customer->user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $booking->customer->user->email }}</p>
                        </div>
                        <div class="sm:text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Layanan Oleh</p>
                            <p class="text-sm font-medium text-gray-900">{{ $booking->caregiver->user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $booking->caregiver->service_area }}</p>
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
                                                Aktual: {{ $booking->check_in_at->format('H:i') }} - {{ $booking->check_out_at->format('H:i') }}
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

                    <!-- Total -->
                    <div class="mt-4 flex justify-end">
                        <div class="w-full sm:w-72 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Subtotal</span>
                                <span class="text-gray-900">Rp {{ number_format($billableTotal, 0, ',', '.') }}</span>
                            </div>
                            @if($booking->status === \App\Models\Booking::STATUS_CANCELLED && (float) $booking->cancellation_fee > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Biaya pembatalan ({{ 100 - $booking->refund_percent }}%)</span>
                                    <span class="text-gray-900">Rp {{ number_format($booking->cancellation_fee, 0, ',', '.') }}</span>
                                </div>
                            @endif
                            @if($booking->status === \App\Models\Booking::STATUS_CANCELLED && (float) $booking->refund_amount > 0)
                                <div class="flex justify-between text-green-700">
                                    <span>Refund ({{ $booking->refund_percent }}%)</span>
                                    <span>- Rp {{ number_format($booking->refund_amount, 0, ',', '.') }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between pt-2 border-t border-gray-200 text-base font-bold">
                                <span class="text-gray-900">Total</span>
                                <span class="text-indigo-600">Rp {{ number_format($billableTotal, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    @if($actual !== null && ! $isCustomer)
                        <!-- Rincian komisi & pendapatan (hanya untuk caregiver & admin) -->
                        <div class="mt-5 pt-5 border-t border-gray-200">
                            <div class="flex items-start gap-3 rounded-md border border-indigo-100 bg-indigo-50/60 px-4 py-3">
                                <x-lucide-coins class="w-5 h-5 text-indigo-500 mt-0.5" />
                                <div class="text-sm">
                                    <p class="font-medium text-gray-900">Rincian Pendapatan Caregiver</p>
                                    <p class="text-gray-700">
                                        Durasi ditagih: <span class="font-medium">{{ $billableHours }} jam</span>
                                        &middot; Komisi platform ({{ (int) config('booking.commission.platform_percent') }}%):
                                        <span class="font-medium">Rp {{ number_format($booking->platformCommission(), 0, ',', '.') }}</span>
                                        &middot; Pendapatan caregiver:
                                        <span class="font-medium text-indigo-700">Rp {{ number_format($booking->caregiverEarnings(), 0, ',', '.') }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Status pembayaran -->
                    <div class="mt-5 pt-5 border-t border-gray-200">
                        <div class="flex items-center gap-2 text-sm">
                            <x-lucide-wallet class="w-4 h-4 text-gray-400" />
                            @if($payment && $payment->status === 'paid')
                                <span class="text-green-700 font-medium">
                                    Lunas pada {{ $payment->paid_at->format('d M Y, H:i') }} ({{ $payment->payment_code }})
                                </span>
                            @elseif($payment && $payment->status === 'pending')
                                <span class="text-amber-700 font-medium">
                                    Menunggu pembayaran &middot; {{ $payment->payment_code }}
                                </span>
                            @elseif($payment && $payment->status === 'failed')
                                <span class="text-red-700 font-medium">
                                    Pembayaran gagal &middot; {{ $payment->payment_code }}
                                </span>
                            @else
                                <span class="text-gray-500">Belum ada pembayaran tercatat untuk booking ini.</span>
                            @endif
                        </div>

                        @if($isCustomer)
                            @php
                                $canPay = $payment
                                    ? in_array($payment->status, ['pending', 'failed'], true)
                                    : in_array($booking->status, [\App\Models\Booking::STATUS_ACCEPTED, \App\Models\Booking::STATUS_CONFIRMED], true);
                            @endphp
                            @if($canPay)
                                <div class="mt-3">
                                    <a href="{{ route('payment.checkout', $booking) }}"
                                        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                        <x-lucide-credit-card class="w-4 h-4" />
                                        @if($payment && $payment->status === 'failed')
                                            Coba Bayar Lagi
                                        @else
                                            Bayar Sekarang
                                        @endif
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
</x-app-layout>