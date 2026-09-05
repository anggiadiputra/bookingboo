<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl {{ $payment['enabled'] ? 'bg-emerald-100' : 'bg-amber-100' }} flex items-center justify-center">
                <x-lucide-credit-card class="w-5 h-5 {{ $payment['enabled'] ? 'text-emerald-700' : 'text-amber-700' }}" />
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-900">Payment Gateway (Midtrans)</h3>
                <p class="text-sm text-gray-500">Kredensial Snap Midtrans untuk pembayaran customer.</p>
            </div>
        </div>

        @if ($payment['enabled'])
            <div class="mb-6 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-md text-sm">
                Payment gateway <strong>aktif</strong> (mode {{ $payment['is_production'] ? 'produksi' : 'sandbox' }}).
            </div>
        @else
            <div class="mb-6 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-md text-sm">
                Payment gateway <strong>nonaktif</strong> — aplikasi berjalan dalam <strong>mode simulasi</strong> (token lokal, tanpa transaksi sungguhan). Isi Server Key & Client Key untuk mengaktifkan.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5 max-w-2xl">
            @csrf
            @method('PATCH')
            <input type="hidden" name="tab" value="payment" />

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Server Key</label>
                <input type="password" name="server_key" value="{{ old('server_key', $payment['server_key']) }}" placeholder="SB-Mid-server-xxxx"
                       autocomplete="new-password"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                <p class="text-xs text-gray-400 mt-1">Disimpan di database (panel ini), bukan di .env.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client Key</label>
                <input type="password" name="client_key" value="{{ old('client_key', $payment['client_key']) }}" placeholder="SB-Mid-client-xxxx"
                       autocomplete="new-password"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Environment</label>
                <select name="is_production" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="0" @selected((int) old('is_production', $payment['is_production']) === 0)>Sandbox (pengembangan)</option>
                    <option value="1" @selected((int) old('is_production', $payment['is_production']) === 1)>Produksi</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Gunakan sandbox untuk uji coba; produksi hanya dengan kredensial asli.</p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none transition ease-in-out duration-150">
                    <x-lucide-save class="w-4 h-4 mr-2" />
                    Simpan Payment Gateway
                </button>
            </div>
        </form>
    </div>
</div>
