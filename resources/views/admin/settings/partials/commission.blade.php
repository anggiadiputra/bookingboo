<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                <x-lucide-percent class="w-5 h-5 text-gray-700" />
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-900">Komisi Platform & Kebijakan Refund</h3>
                <p class="text-sm text-gray-500">Diterapkan pada perhitungan biaya booking dan pembatalan. Perubahan berlaku untuk transaksi baru.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5 max-w-2xl">
            @csrf
            @method('PATCH')
            <input type="hidden" name="tab" value="commission" />

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Komisi Platform (%)</label>
                    <input type="number" name="platform_percent" min="0" max="100" value="{{ old('platform_percent', $commission['platform_percent']) }}" required
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                    <p class="text-xs text-gray-400 mt-1">Potongan dari total layanan.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Refund Pembatalan Mendadak (%)</label>
                    <input type="number" name="default_refund_percent" min="0" max="100" value="{{ old('default_refund_percent', $commission['default_refund_percent']) }}" required
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                    <p class="text-xs text-gray-400 mt-1">Customer batal &lt; ambang terkecil (saat ini &lt; 24 jam).</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Refund Batal Caregiver (%)</label>
                    <input type="number" name="caregiver_refund_percent" min="0" max="100" value="{{ old('caregiver_refund_percent', $commission['caregiver_refund_percent']) }}" required
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                    <p class="text-xs text-gray-400 mt-1">Pembatalan oleh caregiver = refund penuh.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batas Check-in (jam)</label>
                    <input type="number" name="grace_hours" min="0" max="72" value="{{ old('grace_hours', $commission['grace_hours']) }}" required
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                    <p class="text-xs text-gray-400 mt-1">Toleransi check-in setelah end_time.</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ambang Refund Customer (jam sebelum jadwal → % refund)</label>
                <div class="space-y-2" id="tier-rows">
                    @php $tiers = $commission['tiers']; @endphp
                    @forelse($tiers as $i => $tier)
                        <div class="flex items-center gap-2 tier-row">
                            <input type="number" name="tiers[{{ $i }}][hours]" value="{{ $tier['hours'] }}" min="0" placeholder="Jam"
                                   class="w-28 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required />
                            <span class="text-gray-400 text-xs">jam →</span>
                            <input type="number" name="tiers[{{ $i }}][percent]" value="{{ $tier['percent'] }}" min="0" max="100" placeholder="% refund"
                                   class="w-28 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required />
                            <button type="button" onclick="this.closest('.tier-row').remove()"
                                    class="inline-flex items-center px-2 py-1.5 text-gray-400 hover:text-red-600 rounded-md text-xs" title="Hapus ambang">
                                <x-lucide-trash-2 class="w-4 h-4" />
                            </button>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Belum ada ambang refund. Tambahkan minimal satu baris.</p>
                    @endforelse
                </div>
                <button type="button" id="add-tier"
                        class="mt-2 inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-semibold text-gray-700 hover:bg-gray-50">
                    <x-lucide-plus class="w-4 h-4 mr-1" />
                    Tambah Ambang
                </button>
                <p class="text-xs text-gray-400 mt-2">Contoh: 48 jam → 100%, 24 jam → 50%. Sistem memakai ambang terbesar yang terlewati.</p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none transition ease-in-out duration-150">
                    <x-lucide-save class="w-4 h-4 mr-2" />
                    Simpan Komisi & Refund
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        document.getElementById('add-tier').addEventListener('click', function () {
            const rows = document.querySelectorAll('#tier-rows .tier-row');
            const index = rows.length;
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2 tier-row';
            div.innerHTML = `
                <input type="number" name="tiers[${index}][hours]" value="" min="0" placeholder="Jam"
                       class="w-28 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required />
                <span class="text-gray-400 text-xs">jam →</span>
                <input type="number" name="tiers[${index}][percent]" value="" min="0" max="100" placeholder="% refund"
                       class="w-28 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required />
                <button type="button" onclick="this.closest('.tier-row').remove()"
                        class="inline-flex items-center px-2 py-1.5 text-gray-400 hover:text-red-600 rounded-md text-xs" title="Hapus ambang">
                    <x-lucide-trash-2 class="w-4 h-4" />
                </button>`;
            document.getElementById('tier-rows').appendChild(div);
        });
    </script>
@endpush
