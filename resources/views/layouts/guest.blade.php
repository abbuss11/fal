@php
    $currentLocale = app()->getLocale();
    $otherLocale = $currentLocale === 'fr' ? 'en' : 'fr';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FAL') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="client-page antialiased">
        <div class="pointer-events-none absolute left-0 top-16 h-72 w-72 rounded-full bg-cyan-300/25 blur-3xl"></div>
        <div class="pointer-events-none absolute right-0 top-0 h-80 w-80 rounded-full bg-sky-300/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 left-1/3 h-80 w-80 rounded-full bg-teal-200/25 blur-3xl"></div>

        <div class="relative min-h-screen px-4 py-10 sm:px-6">
            <div class="client-shell">
                <div class="saas-shell flex items-center justify-between lg:grid-cols-1">
                     <!---
                    <section class="saas-hero flex flex-col justify-between">
                       
                        <div class="saas-hero-content space-y-4">
                            <div class="flex items-center justify-between gap-2">
                                <a href="/" class="inline-flex items-center gap-3">
                                    <x-application-logo class="h-12 w-12 text-slate-900" />
                                    <div>
                                        <p class="fal-brand-kicker">{{ __('ui.nav.brand_kicker') }}</p>
                                        <p class="text-lg font-semibold text-slate-900">{{ config('app.name', 'FAL PMS') }}</p>
                                    </div>
                                </a>
                                <a
                                    href="{{ route('locale.switch', $otherLocale) }}"
                                    class="client-button-muted !px-2.5 !py-1.5 !text-[11px] !font-semibold"
                                    title="{{ $otherLocale === 'fr' ? __('ui.locale.switch_to_fr') : __('ui.locale.switch_to_en') }}"
                                >
                                    {{ strtoupper($otherLocale) }}
                                </a>
                            </div>
 
                            <h1 class="max-w-xl text-3xl font-semibold leading-tight text-slate-900 sm:text-4xl">
                                {{ __('ui.guest.hero_title') }}
                            </h1>
                            <p class="max-w-xl text-sm leading-relaxed text-slate-600 sm:text-base">
                                {{ __('ui.guest.hero_description') }}
                            </p>

                            <div class="grid gap-2 sm:grid-cols-2">
                                <article class="saas-list-item">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('ui.guest.metric_portfolio') }}</p>
                                    <p class="mt-1 text-2xl font-semibold text-slate-900">Live</p>
                                    <p class="text-xs text-slate-500">{{ __('ui.guest.metric_portfolio_desc') }}</p>
                                </article>
                                <article class="saas-list-item">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('ui.guest.metric_execution') }}</p>
                                    <p class="mt-1 text-2xl font-semibold text-slate-900">Realtime</p>
                                    <p class="text-xs text-slate-500">{{ __('ui.guest.metric_execution_desc') }}</p>
                                </article>
                            </div>
                            
                        </div>
                        

                    </section> 
                    --->
                    <section class="client-panel client-grid-bg w-full px-6 py-6 sm:px-8">
                        {{ $slot }}
                    </section>
                </div>
            </div>
        </div>
    </body>
</html>
