@php
    $currentLocale = app()->getLocale();
    $otherLocale = $currentLocale === 'fr' ? 'en' : 'fr';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'FAL PMS') }} - SaaS Workspace</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="client-page antialiased">
        <div class="pointer-events-none absolute -left-24 top-24 h-80 w-80 rounded-full bg-cyan-300/30 blur-3xl"></div>
        <div class="pointer-events-none absolute right-0 top-0 h-[22rem] w-[22rem] rounded-full bg-sky-300/25 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-0 left-1/3 h-80 w-80 rounded-full bg-teal-200/20 blur-3xl"></div>

        <header class="relative border-b border-[var(--client-line)] bg-white/80 backdrop-blur-xl">
            <div class="client-shell flex min-h-[5.2rem] items-center justify-between gap-3 py-2">
                <a href="/" class="inline-flex items-center gap-3">
                    <x-application-logo class="h-10 w-10 text-slate-900" />
                    <div class="leading-tight">
                        <p class="fal-brand-kicker">{{ __('ui.nav.brand_kicker') }}</p>
                        <p class="text-base font-semibold text-slate-900">{{ config('app.name', 'FAL PMS') }}</p>
                    </div>
                </a>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-2 sm:gap-3">
                        <a
                            href="{{ route('locale.switch', $otherLocale) }}"
                            class="client-button-muted !px-2.5 !py-1.5 !text-[11px] !font-semibold"
                            title="{{ $otherLocale === 'fr' ? __('ui.locale.switch_to_fr') : __('ui.locale.switch_to_en') }}"
                        >
                            {{ strtoupper($otherLocale) }}
                        </a>
                        @auth
                            <a href="{{ route('client.dashboard') }}" class="client-button">{{ __('ui.welcome.open_cockpit') }}</a>
                            @if (auth()->user()->isAdmin())
                                <a href="{{ route('admin.portal') }}" class="client-button-muted">{{ __('ui.nav.admin_short') }}</a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="client-button-muted">{{ __('ui.welcome.login') }}</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="client-button">{{ __('ui.welcome.trial') }}</a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        <main class="relative pb-16">
            <section class="client-shell pb-12 pt-12 lg:pt-16">
                <div class="saas-shell grid items-center gap-6 lg:grid-cols-[1.02fr_0.98fr]">
                    <div class="space-y-5">
                        <span class="client-badge">{{ __('ui.welcome.badge') }}</span>
                        <h1 class="text-balance text-4xl font-semibold leading-tight sm:text-5xl lg:text-6xl client-heading-accent">
                            {{ __('ui.welcome.title') }}
                        </h1>
                        <p class="max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">{{ __('ui.welcome.subtitle') }}</p>

                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            @auth
                                <a href="{{ route('client.dashboard') }}" class="client-button">{{ __('ui.welcome.open_cockpit') }}</a>
                                <a href="{{ route('client.projects.index') }}" class="client-button-muted">{{ __('ui.welcome.project_portfolio') }}</a>
                            @else
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="client-button">{{ __('ui.welcome.start') }}</a>
                                @endif
                                <a href="{{ route('login') }}" class="client-button-muted">{{ __('ui.welcome.already_account') }}</a>
                            @endauth
                        </div>
                    </div>

                    <div class="saas-hero">
                        <div class="saas-hero-content space-y-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <article class="saas-kpi-card">
                                    <p class="saas-kpi-label">Projets actifs</p>
                                    <p class="saas-kpi-value">27</p>
                                    <p class="saas-kpi-help">Pipeline multi-equipes</p>
                                </article>
                                <article class="saas-kpi-card">
                                    <p class="saas-kpi-label">Livraison</p>
                                    <p class="saas-kpi-value">84%</p>
                                    <p class="saas-kpi-help">Objectifs tenus ce mois</p>
                                </article>
                            </div>

                            <article class="saas-panel">
                                <div class="flex items-center justify-between text-sm">
                                    <p class="font-semibold text-slate-800">Sante portefeuille</p>
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Stable</span>
                                </div>
                                <div class="mt-3 h-2 rounded-full bg-slate-200">
                                    <div class="h-2 rounded-full bg-gradient-to-r from-[var(--client-accent)] to-[var(--client-teal)]" style="width: 81%;"></div>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">Synchronisation des equipes, charge et delais sous controle.</p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>
<!---
            <section class="client-shell py-3">
                <div class="saas-kpi-grid">
                    <article class="saas-kpi-card">
                        <p class="saas-kpi-label">Space de control</p>
                        <p class="saas-kpi-value">Dashboard live</p>
                        <p class="saas-kpi-help">KPIs &tendances hebdo</p>
                    </article>
                    <article class="saas-kpi-card">
                        <p class="saas-kpi-label">Execution</p>
                        <p class="saas-kpi-value">Tableau + Calendrier</p>
                        <p class="saas-kpi-help">Flux et timeline terrain</p>
                    </article>
                    <article class="saas-kpi-card">
                        <p class="saas-kpi-label">Collaboration</p>
                        <p class="saas-kpi-value">Chat + Files</p>
                        <p class="saas-kpi-help">Conversations et livrables versionnes</p>
                    </article>
                    <article class="saas-kpi-card">
                        <p class="saas-kpi-label">Governance</p>
                        <p class="saas-kpi-value">Reports</p>
                        <p class="saas-kpi-help">Exports JSON/PDF et pilotage management</p>
                    </article>
                </div>
            </section>

            <section class="client-shell pt-6">
                <div class="grid gap-5 lg:grid-cols-3">
                    <article class="saas-panel">
                        <p class="saas-kpi-label">1. Alignement</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900">{{ __('ui.welcome.section_1_title') }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('ui.welcome.section_1_desc') }}</p>
                    </article>

                    <article class="saas-panel">
                        <p class="saas-kpi-label">2. Execution</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900">{{ __('ui.welcome.section_2_title') }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('ui.welcome.section_2_desc') }}</p>
                    </article>

                    <article class="saas-panel">
                        <p class="saas-kpi-label">3. Controle</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900">{{ __('ui.welcome.section_3_title') }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('ui.welcome.section_3_desc') }}</p>
                    </article>
                </div>
            </section>
            --->
        </main>

        <footer class="border-t border-[var(--client-line)] bg-white/75 py-6">
            <div class="client-shell flex flex-col gap-2 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <p>{{ __('ui.welcome.footer_left', ['app' => config('app.name', 'FAL PMS')]) }}</p>
                <p>{{ __('ui.welcome.footer_right') }}</p>
            </div>
        </footer>
    </body>
</html>
