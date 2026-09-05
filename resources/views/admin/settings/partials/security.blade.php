<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl {{ $security['enabled'] ? 'bg-emerald-100' : 'bg-gray-100' }} flex items-center justify-center">
                <x-lucide-shield-check class="w-5 h-5 {{ $security['enabled'] ? 'text-emerald-700' : 'text-gray-500' }}" />
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-900">Cloudflare Turnstile</h3>
                <p class="text-sm text-gray-500">Proteksi captcha pada form publik (registrasi, login, lupa password).</p>
            </div>
        </div>

        @if ($security['enabled'])
            <div class="mb-6 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-md text-sm">
                Turnstile <strong>aktif</strong> — form publik dilindungi.
            </div>
        @else
            <div class="mb-6 px-4 py-3 bg-gray-50 border border-gray-200 text-gray-600 rounded-md text-sm">
                Turnstile <strong>nonaktif</strong> — form publik tidak memakai verifikasi captcha. Aktifkan hanya jika Anda sudah punya site key & secret key dari <a href="https://dash.cloudflare.com" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Cloudflare Dashboard</a>.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5 max-w-2xl">
            @csrf
            @method('PATCH')
            <input type="hidden" name="tab" value="security" />

            <div class="flex items-center">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="enabled" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           @checked((bool) old('enabled', $security['enabled'])) />
                    Aktifkan Turnstile
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Site Key</label>
                <input type="text" name="site_key" value="{{ old('site_key', $security['site_key']) }}" placeholder="0x4AAAAAAA..."
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                <p class="text-xs text-gray-400 mt-1">Untuk development, test key: <code>1x00000000000000000000AA</code></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
                <input type="password" name="secret_key" value="{{ old('secret_key', $security['secret_key']) }}" placeholder="0x4AAAAAAA..."
                       autocomplete="new-password"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                <p class="text-xs text-gray-400 mt-1">Untuk development, test key: <code>1x0000000000000000000000000000000AA</code></p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none transition ease-in-out duration-150">
                    <x-lucide-save class="w-4 h-4 mr-2" />
                    Simpan Pengaturan Keamanan
                </button>
            </div>
        </form>
    </div>
</div>
