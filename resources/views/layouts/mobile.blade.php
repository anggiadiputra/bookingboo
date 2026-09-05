<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name', 'BookingBoo') }}</title>

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 bg-slate-100 min-h-full selection:bg-rose-500 selection:text-white">
        <!-- Centered Mobile Container Wrapper (Phone Fullscreen on Mobile, Locked Max-W-MD Centered on Desktop) -->
        <div class="w-full max-w-md mx-auto min-h-screen bg-white shadow-2xl flex flex-col relative pb-20 border-x border-slate-200/80">
            
            <!-- Session Alert / Status Toast -->
            @if (session('status'))
                <div class="mx-4 mt-3 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-2">
                    <x-lucide-check-circle-2 class="w-4 h-4 text-emerald-600 shrink-0" />
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if (session('error') || $errors->any())
                <div class="mx-4 mt-3 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium flex items-center gap-2">
                    <x-lucide-alert-circle class="w-4 h-4 text-rose-600 shrink-0" />
                    <span>{{ session('error') ?? $errors->first() }}</span>
                </div>
            @endif

            <!-- Main Content Area -->
            <main class="flex-1">
                {{ $slot }}
            </main>

            <!-- Sticky/Floating Mobile Bottom Navigation Bar -->
            @if(!isset($hideBottomNav) || !$hideBottomNav)
                <x-mobile-bottom-nav />
            @endif
        </div>

        @stack('scripts')
    </body>
</html>
