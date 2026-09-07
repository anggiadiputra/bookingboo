@php
    $isCustomer = auth()->check() && auth()->user()->isCustomer();
    $isCaregiver = auth()->check() && auth()->user()->isCaregiver();
    $isStaff = auth()->check() && auth()->user()->isStaff();

    $homeRoute = url('/');
    $bookingsRoute = $isCustomer
        ? route('customer.bookings.index')
        : ($isCaregiver ? route('caregiver.bookings.index') : route('login'));
    $profileRoute = ! auth()->check()
        ? route('login')
        : ($isCustomer || $isCaregiver ? route('dashboard') : route('profile.edit'));

    $isHome = request()->is('/');
    $isCaregivers = request()->routeIs('caregivers.*');
    $isBookings = request()->routeIs('*.bookings.*');
    $isChat = request()->routeIs('chat.index');
    $isHelp = request()->routeIs('help');
    $isProfile = request()->routeIs('dashboard') || request()->routeIs('profile.*') || request()->routeIs('customer.profile.*') || request()->routeIs('caregiver.profile.*')
        || request()->routeIs('login') || request()->routeIs('register');

    // Item ke-4: Chat (customer & caregiver) atau Bantuan (guest/staff).
    $isParticipant = $isCustomer || $isCaregiver;
    $fourthRoute = $isParticipant ? route('chat.index') : route('help');
    $isFourthActive = $isParticipant ? $isChat : $isHelp;
@endphp

<div class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur border-t border-slate-200/80 shadow-nav w-full max-w-md mx-auto">
    <div class="grid grid-cols-5 h-16 items-center px-1">
        <!-- 1. Beranda -->
        <a href="{{ $homeRoute }}" class="flex flex-col items-center justify-center py-1 transition-colors {{ $isHome ? 'text-brand font-semibold' : 'text-slate-500 hover:text-slate-800' }}">
            <x-lucide-home class="w-5 h-5 {{ $isHome ? 'stroke-[2.5]' : 'stroke-2' }}" />
            <span class="text-[11px] mt-1 tracking-tight">Beranda</span>
        </a>

        <!-- 2. Cari Pendamping -->
        <a href="{{ route('caregivers.index') }}" class="flex flex-col items-center justify-center py-1 transition-colors {{ $isCaregivers ? 'text-brand font-semibold' : 'text-slate-500 hover:text-slate-800' }}">
            <x-lucide-search class="w-5 h-5 {{ $isCaregivers ? 'stroke-[2.5]' : 'stroke-2' }}" />
            <span class="text-[11px] mt-1 tracking-tight">Cari</span>
        </a>

        <!-- 3. Riwayat / Aktivitas -->
        <a href="{{ $bookingsRoute }}" class="flex flex-col items-center justify-center py-1 transition-colors {{ $isBookings ? 'text-brand font-semibold' : 'text-slate-500 hover:text-slate-800' }}">
            <div class="relative">
                <x-lucide-clipboard-list class="w-5 h-5 {{ $isBookings ? 'stroke-[2.5]' : 'stroke-2' }}" />
            </div>
            <span class="text-[11px] mt-1 tracking-tight">{{ $isCaregiver ? 'Pesanan' : 'Riwayat' }}</span>
        </a>

        <!-- 4. Chat (peserta) / Bantuan (guest-staff) -->
        <a href="{{ $fourthRoute }}" class="flex flex-col items-center justify-center py-1 transition-colors {{ $isFourthActive ? 'text-brand font-semibold' : 'text-slate-500 hover:text-slate-800' }}">
            @if($isParticipant)
                <x-lucide-message-circle class="w-5 h-5 {{ $isFourthActive ? 'text-brand stroke-[2.5]' : 'text-slate-500 stroke-2' }}" />
                <span class="text-[11px] mt-1 tracking-tight">Chat</span>
            @else
                <x-lucide-siren class="w-5 h-5 {{ $isFourthActive ? 'text-brand stroke-[2.5]' : 'text-rose-500 stroke-2' }}" />
                <span class="text-[11px] mt-1 tracking-tight">Bantuan</span>
            @endif
        </a>

        <!-- 5. Profil / Akun -->
        <a href="{{ $profileRoute }}" class="flex flex-col items-center justify-center py-1 transition-colors {{ $isProfile ? 'text-brand font-semibold' : 'text-slate-500 hover:text-slate-800' }}">
            <x-lucide-user class="w-5 h-5 {{ $isProfile ? 'stroke-[2.5]' : 'stroke-2' }}" />
            <span class="text-[11px] mt-1 tracking-tight">{{ auth()->check() ? 'Profil' : 'Masuk' }}</span>
        </a>
    </div>
</div>
