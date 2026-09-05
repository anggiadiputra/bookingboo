<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#f0f2f5]">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' - ' : '' }}Admin Panel {{ config('app.name', 'BookingBoo') }}</title>

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-[#f0f2f5] min-h-screen" x-data="{ sidebarOpen: false }">
        
        <!-- Mobile Sidebar Backdrop -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/40 backdrop-blur-xs z-40 lg:hidden" 
             style="display: none;"></div>

        <!-- Desktop / Mobile Sidebar Navigation -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white text-gray-700 flex flex-col border-r border-gray-200 transition-transform duration-200 ease-in-out lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            
            <!-- Sidebar Header -->
            <div class="h-14 flex items-center justify-between px-5 border-b border-gray-200 bg-white shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-black flex items-center justify-center text-white font-bold text-base">
                        B
                    </div>
                    <div class="leading-none">
                        <span class="font-bold text-gray-900 tracking-tight text-base">Booking<span class="text-rose-500">Boo</span></span>
                        <span class="block text-[10px] text-gray-400 uppercase tracking-wider font-bold mt-0.5">Staff Portal</span>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-gray-900 p-1">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>

            <!-- Staff Identity Badge -->
            <div class="px-3.5 py-3 bg-gray-50 border border-gray-200 m-3 rounded-xl flex items-center justify-between shrink-0">
                <div>
                    <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">ID Staf</p>
                    <p class="text-sm font-bold text-gray-900 font-mono">{{ auth()->user()->public_id }}</p>
                </div>
                @if(auth()->user()->isAdmin())
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-black text-white border-black">
                        admin
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-gray-100 text-gray-700 border-gray-200">
                        {{ auth()->user()->role }}
                    </span>
                @endif
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-2 space-y-1 overflow-y-auto text-sm">
                <a href="{{ route('dashboard') }}" 
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('dashboard') || request()->routeIs('admin.dashboard') || request()->routeIs('finance.dashboard') || request()->routeIs('support.dashboard') ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <x-lucide-layout-dashboard class="w-4 h-4 shrink-0" />
                    <span>Dashboard Utama</span>
                </a>

                @if(auth()->user()->isAdmin())
                    <div class="pt-4 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Verifikasi & Penugasan</div>

                    <a href="{{ route('admin.caregivers.index') }}" 
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('admin.caregivers.*') ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        <x-lucide-badge-check class="w-4 h-4 shrink-0" />
                        <span>Verifikasi Caregiver</span>
                    </a>

                    <a href="{{ route('admin.bookings.replacement.index') }}" 
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('admin.bookings.replacement.*') ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        <x-lucide-refresh-cw class="w-4 h-4 shrink-0" />
                        <span>Caregiver Pengganti</span>
                    </a>

                    <a href="{{ route('admin.staff.index') }}" 
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('admin.staff.*') ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        <x-lucide-users class="w-4 h-4 shrink-0" />
                        <span>Manajemen Staf</span>
                    </a>
                @endif

                @if(auth()->user()->isAdmin() || auth()->user()->isFinance())
                    <div class="pt-4 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Finansial</div>

                    <a href="{{ route('admin.transactions.index') }}" 
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('admin.transactions.*') ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        <x-lucide-receipt class="w-4 h-4 shrink-0" />
                        <span>Daftar Transaksi</span>
                    </a>

                    <a href="{{ route('admin.payouts.index') }}" 
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('admin.payouts.*') ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        <x-lucide-coins class="w-4 h-4 shrink-0" />
                        <span>Pencairan Dana (Payout)</span>
                    </a>
                @endif

                @if(auth()->user()->isAdmin() || auth()->user()->isSupport())
                    <div class="pt-4 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Layanan & Sengketa</div>

                    <a href="{{ route('complaints.staff.index') }}" 
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('complaints.staff.*') ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        <x-lucide-flag class="w-4 h-4 shrink-0" />
                        <span>Panel Komplain</span>
                    </a>
                @endif

                @if(auth()->user()->isAdmin())
                    <div class="pt-4 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Moderasi Sistem</div>

                    <a href="{{ route('admin.reviews.index') }}" 
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('admin.reviews.*') ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        <x-lucide-message-square class="w-4 h-4 shrink-0" />
                        <span>Moderasi Ulasan</span>
                    </a>

                    <a href="{{ route('admin.users.index') }}" 
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('admin.users.*') ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        <x-lucide-shield-alert class="w-4 h-4 shrink-0" />
                        <span>Moderasi Akun</span>
                    </a>
                @endif

                <div class="pt-4 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Dukungan</div>

                <a href="{{ route('admin.reports.index') }}" 
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('admin.reports.*') ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <x-lucide-chart-column class="w-4 h-4 shrink-0" />
                    <span>Laporan Transaksi</span>
                </a>

                <a href="{{ route('help') }}" 
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('help') ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <x-lucide-life-buoy class="w-4 h-4 shrink-0" />
                    <span>Pusat Bantuan & Darurat</span>
                </a>

                @if(auth()->user()->isAdmin())
                    <div class="pt-4 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Pengaturan</div>

                    <a href="{{ route('admin.settings.index') }}" 
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('admin.settings.*') ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        <x-lucide-settings class="w-4 h-4 shrink-0" />
                        <span>Pengaturan Platform</span>
                    </a>

                    <a href="{{ route('admin.announcements.index') }}" 
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('admin.announcements.*') ? 'bg-black text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        <x-lucide-megaphone class="w-4 h-4 shrink-0" />
                        <span>Papan Pengumuman</span>
                    </a>
                @endif
            </nav>

            <!-- Sidebar Footer -->
            <div class="p-3 border-t border-gray-200 bg-gray-50/50 shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-100">
                        <x-lucide-log-out class="w-4 h-4" />
                        <span>Keluar dari Akun</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area: Padded left for fixed desktop sidebar, 100% full width -->
        <div class="lg:pl-64 flex flex-col min-h-screen w-full">
            
            <!-- Desktop Top Bar Header (h-14, sticky, white bg) -->
            <header class="h-14 bg-white border-b border-gray-200 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 lg:px-8 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 shrink-0">
                        <x-lucide-menu class="w-5 h-5" />
                    </button>
                    <div class="flex items-center gap-2 text-sm min-w-0">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-1.5 font-medium text-gray-500 hover:text-black transition-colors shrink-0">
                            <x-lucide-shield class="w-4 h-4 text-gray-700 shrink-0" />
                            <span class="hidden sm:inline">Portal Staf</span>
                        </a>
                        <x-lucide-chevron-right class="w-3.5 h-3.5 text-gray-300 shrink-0 hidden sm:inline" />
                        <span class="font-bold text-gray-900 text-sm truncate">
                            @if(!empty($title))
                                {{ strip_tags((string) $title) }}
                            @elseif(isset($header) && trim(strip_tags((string) $header)) !== '')
                                {{ trim(strip_tags((string) $header)) }}
                            @else
                                Operasional
                            @endif
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 hover:bg-gray-50 text-xs font-semibold rounded-lg text-gray-700 transition-colors" title="Buka Beranda Situs">
                        <x-lucide-globe class="w-3.5 h-3.5 text-gray-500" />
                        <span class="hidden sm:inline">Lihat Beranda</span>
                    </a>

                    <div class="h-5 w-px bg-gray-200 hidden sm:block"></div>

                    <a href="{{ route('notifications.index') }}" class="relative w-9 h-9 rounded-lg border border-gray-200 hover:bg-gray-50 flex items-center justify-center text-gray-500 transition-colors" title="Notifikasi">
                        <x-lucide-bell class="w-4 h-4" />
                        @if(auth()->user()->unreadNotificationsCount() > 0)
                            <span class="absolute -top-1.5 -right-1.5 min-w-[17px] h-[17px] px-1 rounded-full bg-rose-500 text-white text-[9px] font-bold flex items-center justify-center ring-2 ring-white">
                                {{ auth()->user()->unreadNotificationsCount() > 9 ? '9+' : auth()->user()->unreadNotificationsCount() }}
                            </span>
                        @endif
                    </a>

                    <div class="hidden sm:flex flex-col text-right">
                        <span class="text-xs font-bold text-gray-900 leading-tight">{{ auth()->user()->name }}</span>
                        <span class="text-[10px] text-gray-400">{{ auth()->user()->email }}</span>
                    </div>
                    <div class="w-8 h-8 rounded-lg bg-black text-white font-bold flex items-center justify-center text-xs shadow-xs">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold rounded-lg transition-colors" title="Keluar dari Akun">
                            <x-lucide-log-out class="w-3.5 h-3.5" />
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Page Content Area (FULL WIDTH: w-full p-5 md:p-8) -->
            <main class="flex-1 p-5 md:p-8 w-full">
                @if (session('status'))
                    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center gap-2.5 text-emerald-800 text-sm font-medium">
                        <x-lucide-check-circle-2 class="w-5 h-5 text-emerald-600 shrink-0" />
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if (session('error') || $errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl flex items-center gap-2.5 text-red-800 text-sm font-medium">
                        <x-lucide-alert-circle class="w-5 h-5 text-red-600 shrink-0" />
                        <span>{{ session('error') ?? $errors->first() }}</span>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
