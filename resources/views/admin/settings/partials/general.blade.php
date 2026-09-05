<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                <x-lucide-phone class="w-5 h-5 text-gray-700" />
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-900">Kontak Bantuan & Darurat</h3>
                <p class="text-sm text-gray-500">Ditampilkan di halaman Bantuan & Darurat (/help) untuk customer dan caregiver.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5 max-w-2xl">
            @csrf
            @method('PATCH')
            <input type="hidden" name="tab" value="general" />

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hotline Telepon</label>
                <input type="text" name="hotline" value="{{ old('hotline', $general['hotline']) }}" placeholder="+62 811 0000 0000"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                <p class="text-xs text-gray-400 mt-1">Nomor yang muncul di tombol "Telepon Siaga".</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Official</label>
                <input type="text" name="whatsapp" value="{{ old('whatsapp', $general['whatsapp']) }}" placeholder="+62 811 0000 0000"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                <p class="text-xs text-gray-400 mt-1">Nomor yang dipakai tautan wa.me di halaman bantuan.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Dukungan</label>
                <input type="email" name="email" value="{{ old('email', $general['email']) }}" placeholder="support@bookingboo.test"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jam Operasional</label>
                <input type="text" name="hours" value="{{ old('hours', $general['hours']) }}" placeholder="Senin-Minggu, 07.00-21.00 WIB"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none transition ease-in-out duration-150">
                    <x-lucide-save class="w-4 h-4 mr-2" />
                    Simpan Kontak
                </button>
            </div>
        </form>
    </div>
</div>
