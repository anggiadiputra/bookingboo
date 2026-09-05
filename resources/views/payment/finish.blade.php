<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Pembayaran
        </h2>
    </x-slot>

    <div class="p-4">
        <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-slate-100">
            <div class="p-6 text-center">
                @if($status === 'success')
                    <x-lucide-check-circle class="w-12 h-12 text-green-600 mx-auto" />
                    <h3 class="mt-3 text-lg font-bold text-gray-900">Pembayaran sedang diproses</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Status pembayaran akan diperbarui otomatis setelah konfirmasi dari Midtrans.
                    </p>
                @elseif($status === 'pending')
                    <x-lucide-clock class="w-12 h-12 text-amber-500 mx-auto" />
                    <h3 class="mt-3 text-lg font-bold text-gray-900">Menunggu pembayaran</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Selesaikan pembayaran sebelum batas waktu. Status akan diperbarui via webhook Midtrans.
                    </p>
                @else
                    <x-lucide-alert-circle class="w-12 h-12 text-red-600 mx-auto" />
                    <h3 class="mt-3 text-lg font-bold text-gray-900">Pembayaran tidak selesai</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Silakan coba lagi dari halaman invoice.
                    </p>
                @endif

                <div class="mt-6 flex justify-center gap-3">
                    <a href="{{ route('customer.bookings.invoice', $booking) }}"
                        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        <x-lucide-receipt class="w-4 h-4" />
                        Lihat Invoice
                    </a>
                    <a href="{{ route('customer.bookings.show', $booking) }}"
                        class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Detail Booking
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>