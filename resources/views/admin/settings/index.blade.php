<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pengaturan Platform') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Tabs -->
        <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-3">
            <a href="{{ route('admin.settings.index', ['tab' => 'general']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors {{ $tab === 'general' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                <x-lucide-phone class="w-4 h-4" />
                Kontak & Bantuan
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'payment']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors {{ $tab === 'payment' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                <x-lucide-credit-card class="w-4 h-4" />
                Payment Gateway
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'commission']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors {{ $tab === 'commission' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                <x-lucide-percent class="w-4 h-4" />
                Komisi & Refund
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'security']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors {{ $tab === 'security' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                <x-lucide-shield-check class="w-4 h-4" />
                Keamanan
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'mail']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors {{ $tab === 'mail' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                <x-lucide-mail class="w-4 h-4" />
                Email / SMTP
            </a>
        </div>

        @if ($tab === 'general')
            @include('admin.settings.partials.general', ['general' => $general])
        @elseif ($tab === 'payment')
            @include('admin.settings.partials.payment', ['payment' => $payment])
        @elseif ($tab === 'security')
            @include('admin.settings.partials.security', ['security' => $security])
        @elseif ($tab === 'mail')
            @include('admin.settings.partials.mail', ['mail' => $mail])
        @else
            @include('admin.settings.partials.commission', ['commission' => $commission])
        @endif
    </div>
</x-app-layout>
