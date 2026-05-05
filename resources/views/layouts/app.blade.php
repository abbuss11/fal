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
        <div class="pointer-events-none absolute -left-24 top-24 h-72 w-72 rounded-full bg-orange-300/30 blur-3xl"></div>
        <div class="pointer-events-none absolute right-0 top-0 h-80 w-80 rounded-full bg-teal-300/25 blur-3xl"></div>

        <div class="relative min-h-screen pb-12">
            @include('layouts.navigation')

            @isset($header)
                <header class="pt-8">
                    <div class="client-shell">
                        <div class="client-panel px-6 py-5 sm:px-8">
                        {{ $header }}
                        </div>
                    </div>
                </header>
            @endisset

            <main class="pt-8">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
