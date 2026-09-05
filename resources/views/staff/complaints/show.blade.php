<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tinjau Komplain') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Detail komplain -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <x-lucide-flag class="w-5 h-5 text-red-500 mr-2" />
                            Komplain: {{ $complaint->booking->booking_code }}
                        </h3>
                        @php $cls = match($complaint->status) {
                            'open' => 'bg-yellow-100 text-yellow-800',
                            'in_review' => 'bg-blue-100 text-blue-800',
                            'resolved' => 'bg-green-100 text-green-800',
                            default => 'bg-red-100 text-red-700',
                        }; @endphp
                        <span class="px-3 py-1 rounded-full text-xs {{ $cls }}">{{ $complaint->labelStatus() }}</span>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500 text-xs uppercase">Reporter</dt>
                            <dd class="mt-1 text-gray-900 font-medium">{{ $complaint->reporter?->name ?? '-' }} ({{ $complaint->reporter?->role }})</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-xs uppercase">Ditangani oleh</dt>
                            <dd class="mt-1 text-gray-900 font-medium">{{ $complaint->handler?->name ?? 'Belum ada' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-xs uppercase">Alasan</dt>
                            <dd class="mt-1 text-gray-900">{{ $complaint->reason }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-xs uppercase">Masuk</dt>
                            <dd class="mt-1 text-gray-900">{{ $complaint->created_at->format('d M Y, H:i') }}</dd>
                        </div>
                    </dl>

                    @if($complaint->description)
                        <div class="mt-4 p-3 bg-gray-50 rounded-md">
                            <p class="text-xs uppercase text-gray-500 mb-1">Uraian masalah</p>
                            <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ $complaint->description }}</p>
                        </div>
                    @endif

                    @if($complaint->resolution)
                        <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                            <p class="text-xs uppercase text-green-700 mb-1">Resolusi</p>
                            <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ $complaint->resolution }}</p>
                        </div>
                    @endif

                    <!-- Info para pihak -->
                    <div class="mt-5 pt-4 border-t border-gray-200 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs uppercase text-gray-500 mb-1">Customer</p>
                            <a href="{{ route('admin.customers.show', $complaint->booking->customer) }}"
                               class="text-gray-900 font-medium hover:text-indigo-600 hover:underline inline-flex items-center gap-1">
                                {{ $complaint->booking->customer->user->name }}
                                <x-lucide-external-link class="w-3 h-3 text-indigo-500" />
                            </a>
                            <p class="text-xs text-gray-500">{{ $complaint->booking->customer->user->email }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500 mb-1">Caregiver</p>
                            <p class="text-gray-900">{{ $complaint->booking->caregiver->user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $complaint->booking->caregiver->user->email }}</p>
                        </div>
                    </div>

                    @php
                        $paidPayment = $complaint->booking->payments()->where('status', \App\Models\Payment::STATUS_PAID)->latest()->first();
                        $maxRefund = $paidPayment ? (float) $complaint->booking->billableTotal() - (float) $paidPayment->refunded_amount : 0;
                    @endphp

                    <div class="mt-5 pt-4 border-t border-gray-200 text-sm text-gray-700 space-y-1">
                        <p>Status booking: <span class="font-medium">{{ $complaint->booking->label() }}</span></p>
                        @if($paidPayment)
                            <p>Pembayaran lunas: <span class="font-medium">{{ $paidPayment->payment_code }}</span>
                                — sisa dapat direfund: <span class="font-medium">Rp {{ number_format($maxRefund, 0, ',', '.') }}</span></p>
                        @endif
                    </div>

                    <a href="{{ route('complaints.staff.index') }}" class="mt-5 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800">
                        <x-lucide-arrow-left class="w-4 h-4 mr-1" />
                        Kembali ke daftar komplain
                    </a>
                </div>
            </div>

            <!-- Form penanganan -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Tindak Lanjut</h4>
                    <form method="POST" action="{{ route('complaints.staff.update', $complaint) }}">
                        @csrf
                        @method('PATCH')
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" name="status"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            @foreach(['open' => 'Baru', 'in_review' => 'Sedang ditinjau', 'resolved' => 'Selesai', 'rejected' => 'Ditolak'] as $s => $label)
                                <option value="{{ $s }}" @selected($complaint->status === $s)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <label for="resolution" class="mt-4 block text-sm font-medium text-gray-700">
                            Resolusi <span class="text-gray-400 text-xs">(wajib saat selesai/ditolak)</span>
                        </label>
                        <textarea id="resolution" name="resolution" rows="3" maxlength="2000"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            placeholder="mis. Refund 50% telah diproses, caregiver diganti.">{{ old('resolution', $complaint->resolution) }}</textarea>
                        @error('resolution')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="mt-4 flex justify-end">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                <x-lucide-save class="w-4 h-4 mr-2" />
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Aksi resolusi sengketa (finance/admin, sengketa aktif) -->
            @if(in_array(auth()->user()->role, ['finance', 'admin']) && in_array($complaint->status, ['open', 'in_review']))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-1 flex items-center">
                            <x-lucide-wallet class="w-4 h-4 text-amber-600 mr-2" />
                            Resolusi Finansial Sengketa
                        </h4>
                        <p class="text-xs text-gray-500 mb-4">Aksi ini hanya dapat dilakukan staf finance atau admin selama sengketa masih aktif.</p>

                        @error('refund')
                            <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('payout')
                            <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <form method="POST" action="{{ route('complaints.resolve.refund', $complaint) }}" class="p-4 border border-gray-200 rounded-lg">
                                @csrf
                                <p class="font-medium text-gray-900 text-sm mb-1 flex items-center">
                                    <x-lucide-undo-2 class="w-4 h-4 text-blue-600 mr-2" />
                                    Catat Refund ke Customer
                                </p>
                                @if($paidPayment)
                                    <p class="text-xs text-gray-500 mb-2">Maksimal Rp {{ number_format($maxRefund, 0, ',', '.') }} (kosongkan untuk refund penuh sisa).</p>
                                    <label for="refund-amount" class="block text-xs font-medium text-gray-700">Nominal refund (Rp)</label>
                                    <input type="number" id="refund-amount" name="amount" min="0" max="{{ $maxRefund }}" step="0.01"
                                        value="{{ old('amount') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                @else
                                    <p class="text-xs text-gray-500 mb-2">Booking ini belum ada pembayaran lunas — refund tidak tersedia.</p>
                                @endif
                                <label for="refund-note" class="mt-2 block text-xs font-medium text-gray-700">Catatan (opsional)</label>
                                <input type="text" id="refund-note" name="note" maxlength="500" value="{{ old('note') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                @if($paidPayment)
                                    <button type="submit"
                                        class="mt-3 inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700">
                                        <x-lucide-undo-2 class="w-3 h-3 mr-1" />
                                        Catat Refund
                                    </button>
                                @endif
                            </form>

                            <form method="POST" action="{{ route('complaints.resolve.payout-cancel', $complaint) }}" class="p-4 border border-gray-200 rounded-lg">
                                @csrf
                                <p class="font-medium text-gray-900 text-sm mb-1 flex items-center">
                                    <x-lucide-ban class="w-4 h-4 text-red-600 mr-2" />
                                    Batalkan Payout Pending Caregiver
                                </p>
                                <p class="text-xs text-gray-500 mb-2">Menahan dana caregiver dengan membatalkan pengajuan pencairan yang masih menunggu.</p>
                                <label for="payout-note" class="block text-xs font-medium text-gray-700">Alasan pembatalan (opsional)</label>
                                <input type="text" id="payout-note" name="note" maxlength="500" value="{{ old('note') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                <button type="submit"
                                    class="mt-3 inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-md hover:bg-red-700">
                                    <x-lucide-ban class="w-3 h-3 mr-1" />
                                    Batalkan Payout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
</x-app-layout>