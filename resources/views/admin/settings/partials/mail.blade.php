<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                <x-lucide-mail class="w-5 h-5 text-gray-700" />
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-900">Pengaturan Email / SMTP</h3>
                <p class="text-sm text-gray-500">Dipakai untuk verifikasi pendaftaran, reset password, dan notifikasi email.</p>
            </div>
        </div>

        @if(!filled($mail['host']))
            <div class="mb-6 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-md text-sm">
                SMTP <strong>belum dikonfigurasi</strong> — email tidak terkirim (masih memakai log). Isi host & kredensial SMTP di bawah untuk mengaktifkan.
            </div>
        @else
            <div class="mb-6 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-md text-sm">
                SMTP <strong>aktif</strong> menuju <strong>{{ $mail['host'] }}:{{ $mail['port'] }}</strong>.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5 max-w-2xl">
            @csrf
            @method('PATCH')
            <input type="hidden" name="tab" value="mail" />

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mailer</label>
                    <select name="mailer" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @foreach (['smtp' => 'SMTP', 'log' => 'Log (debug)', 'sendmail' => 'Sendmail'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('mailer', $mail['mailer']) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Host SMTP</label>
                    <input type="text" name="host" value="{{ old('host', $mail['host']) }}" placeholder="smtp.gmail.com / smtp.hostinger.com"
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                    <input type="number" name="port" value="{{ old('port', $mail['port']) }}" placeholder="587"
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Enkripsi</label>
                    <select name="encryption" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @foreach (['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'Tanpa'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('encryption', $mail['encryption']) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" value="{{ old('username', $mail['username']) }}" placeholder="email@domain.com"
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password / App Password</label>
                <input type="password" name="password" value="{{ old('password', $mail['password']) }}" placeholder="••••••••"
                       autocomplete="new-password"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                <p class="text-xs text-gray-400 mt-1">Untuk Gmail, gunakan App Password (bukan password akun).</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Alamat (From)</label>
                    <input type="email" name="from_address" value="{{ old('from_address', $mail['from_address']) }}" placeholder="no-reply@diurusin.web.id"
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Nama</label>
                    <input type="text" name="from_name" value="{{ old('from_name', $mail['from_name']) }}" placeholder="BookingBoo"
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none transition ease-in-out duration-150">
                    <x-lucide-save class="w-4 h-4 mr-2" />
                    Simpan Pengaturan Email
                </button>
            </div>
        </form>
    </div>
</div>
