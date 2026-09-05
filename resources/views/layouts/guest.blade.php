<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 antialiased bg-slate-50 selection:bg-rose-500 selection:text-white">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-8 sm:py-12">
            <div class="mb-6">
                <a href="/">
                    <x-application-logo class="h-11 w-auto" />
                </a>
            </div>

            <div class="w-full sm:max-w-md p-6 sm:p-8 bg-white border border-slate-200/80 shadow-card rounded-2xl sm:rounded-3xl">
                {{ $slot }}
            </div>

            <div class="mt-8 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} BookingBoo. Layanan Pendampingan Medis & Caregiver Terpercaya.
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
