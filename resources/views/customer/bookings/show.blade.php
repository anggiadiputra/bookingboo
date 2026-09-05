<x-app-layout>
    <!-- Halodoc Mobile Header -->
    <div class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-slate-100 px-4 py-3.5 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('customer.bookings.index') }}" class="w-9 h-9 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors">
                <x-lucide-arrow-left class="w-5 h-5" />
            </a>
            <div>
                <h1 class="text-base font-bold text-slate-900 leading-tight">Detail Reservasi</h1>
                <p class="text-[11px] text-slate-500">Kode: {{ $booking->booking_code }}</p>
            </div>
        </div>
        <a href="{{ route('customer.bookings.invoice', $booking) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-700 rounded-full text-xs font-semibold hover:bg-slate-200 transition-colors" title="Invoice">
            <x-lucide-file-text class="w-3.5 h-3.5 text-rose-500" />
            <span>Invoice</span>
        </a>
    </div>

    <div class="px-4 py-4 space-y-4">
        @include('customer.bookings._detail', ['viewer' => 'customer'])
    </div>
</x-app-layout>