@php
    $hours = (int) ceil($booking->start_time->diffInMinutes($booking->end_time) / 60);
    $total = $booking->estimateTotal();
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Pembayaran
        </h2>
    </x-slot>

    <div class="p-4">
            <div class="mb-4">
                <a href="{{ route('customer.bookings.invoice', $booking) }}"
                    class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800">
                    <x-lucide-arrow-left class="w-4 h-4 mr-1" />
                    Kembali ke invoice
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-200">
                        <x-lucide-credit-card class="w-6 h-6 text-indigo-600" />
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Bayar Invoice {{ $booking->invoiceNumber() }}</h3>
                            <p class="text-sm text-gray-500">Booking {{ $booking->booking_code }}</p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Layanan</p>
                            <p class="font-medium text-gray-900">Pendampingan caregiver ({{ $hours }} jam)</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total</p>
                            <p class="text-xl font-bold text-indigo-600">Rp {{ number_format($total, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    @if($simulated)
                        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <div class="flex items-start gap-2">
                                <x-lucide-info class="w-5 h-5 text-amber-600 mt-0.5" />
                                <div class="text-sm text-amber-800">
                                    <p class="font-medium">Mode Simulasi Pembayaran</p>
                                    <p class="mt-1">Kredensial Midtrans belum dikonfigurasi. Gunakan tombol di bawah untuk
                                        mensimulasikan hasil pembayaran (settlement/kedaluwarsa). Konfigurasi
                                        <code>MIDTRANS_SERVER_KEY</code> dan <code>MIDTRANS_CLIENT_KEY</code> di .env
                                        untuk pembayaran nyata via Snap.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            @if($payment->status === 'pending')
                                <form action="{{ route('payment.settle', $booking) }}" method="POST"
                                    onsubmit="return confirm('Simulasikan pembayaran berhasil untuk invoice ini?')">
                                    @csrf
                                    <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                                        <x-lucide-check-circle class="w-4 h-4" />
                                        Bayar Sekarang (simulasi lunas)
                                    </button>
                                </form>
                                <form action="{{ route('payment.expire', $booking) }}" method="POST"
                                    onsubmit="return confirm('Tandai pembayaran sebagai gagal/kedaluwarsa?')">
                                    @csrf
                                    <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 rounded-md border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        <x-lucide-x-circle class="w-4 h-4" />
                                        Simulasikan gagal / kedaluwarsa
                                    </button>
                                </form>
                            @elseif($payment->status === 'paid')
                                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                                    <div class="flex items-center gap-2">
                                        <x-lucide-check-circle class="w-5 h-5" />
                                        Pembayaran lunas pada {{ $payment->paid_at->format('d M Y, H:i') }}.
                                    </div>
                                </div>
                            @else
                                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                                    <div class="flex items-center gap-2">
                                        <x-lucide-alert-circle class="w-5 h-5" />
                                        Pembayaran sebelumnya gagal. Kembali ke invoice dan coba bayar lagi.
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="mt-5">
                            <button id="pay-button"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                                <x-lucide-credit-card class="w-4 h-4" />
                                Bayar Sekarang
                            </button>
                            <p class="mt-3 text-xs text-gray-500">
                                Pembayaran diproses melalui Midtrans (QRIS, transfer bank, e-wallet, kartu).
                            </p>
                        </div>

                        <script src="{{ $snapUrl }}" data-client-key="{{ $clientKey }}"></script>
                        <script>
                            document.getElementById('pay-button').addEventListener('click', function () {
                                snap.pay('{{ $snapToken }}', {
                                    onSuccess: function (result) {
                                        window.location.href = '{{ route('payment.finish', ['booking' => $booking->id, 'status' => 'success']) }}';
                                    },
                                    onPending: function (result) {
                                        window.location.href = '{{ route('payment.finish', ['booking' => $booking->id, 'status' => 'pending']) }}';
                                    },
                                    onError: function (result) {
                                        window.location.href = '{{ route('payment.finish', ['booking' => $booking->id, 'status' => 'error']) }}';
                                    },
                                    onClose: function () {
                                        // Popup ditutup tanpa pembayaran; payment tetap pending.
                                    }
                                });
                            });
                        </script>
                    @endif

                    <div class="mt-5 pt-4 border-t border-gray-200 text-sm text-gray-500">
                        Status pembayaran saat ini:
                        <span class="font-medium {{ $payment->status === 'paid' ? 'text-green-700' : ($payment->status === 'pending' ? 'text-amber-700' : 'text-red-700') }}">
                            {{ $payment->status === 'paid' ? 'Lunas' : ($payment->status === 'pending' ? 'Menunggu pembayaran' : 'Gagal') }}
                        </span>
                        &middot; {{ $payment->payment_code }}
                    </div>
                </div>
            </div>
        </div>
</x-app-layout>