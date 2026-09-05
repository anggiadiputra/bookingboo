<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="p-4 space-y-4">
        <div class="p-4 sm:p-6 bg-white shadow-sm rounded-2xl border border-slate-100">
            <div>
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-4 sm:p-6 bg-white shadow-sm rounded-2xl border border-slate-100">
            <div>
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="p-4 sm:p-6 bg-white shadow-sm rounded-2xl border border-slate-100">
            <div>
                @include('profile.partials.delete-user-form')
            </div>
        </div>

        <!-- Logout Action -->
        <div class="p-4 sm:p-6 bg-white shadow-sm rounded-2xl border border-slate-100">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Keluar dari Akun</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Akhiri sesi login Anda pada perangkat ini.</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-200 hover:border-rose-600 text-xs font-bold rounded-xl transition-all shadow-xs">
                        <x-lucide-log-out class="w-4 h-4" />
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
