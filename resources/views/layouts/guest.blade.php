<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FAL') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="client-page antialiased">
        <div class="pointer-events-none absolute left-0 top-16 h-72 w-72 rounded-full bg-orange-300/25 blur-3xl"></div>
        <div class="pointer-events-none absolute right-0 top-0 h-80 w-80 rounded-full bg-teal-300/20 blur-3xl"></div>

        <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-10 sm:px-6">
            <div class="mb-6 fade-up" style="animation-delay: 80ms;">
                <a href="/" class="inline-flex items-center gap-3">
                    <x-application-logo class="h-12 w-12 text-white" />
                    <span class="font-semibold tracking-tight text-slate-900">FAL PMS Client</span>
                </a>
            </div>

            <div class="client-panel w-full max-w-md px-6 py-6 sm:px-8 fade-up client-grid-bg" style="animation-delay: 160ms;">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
