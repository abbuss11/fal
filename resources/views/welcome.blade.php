<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'FAL PMS') }} - Futuristic Africa Lab</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="client-page antialiased">
        <div class="pointer-events-none absolute -left-24 top-24 h-80 w-80 rounded-full bg-orange-300/30 blur-3xl"></div>
        <div class="pointer-events-none absolute right-0 top-0 h-[22rem] w-[22rem] rounded-full bg-teal-300/25 blur-3xl"></div>

        <header class="relative border-b border-[var(--client-line)] bg-white/70 backdrop-blur-xl">
            <div class="client-shell flex h-20 items-center justify-between">
                <a href="/" class="inline-flex items-center gap-3">
                    <x-application-logo class="h-10 w-10 text-slate-900" />
                    <div class="leading-tight">
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Gestionnaire des Projets</p>
                        <p class="text-base font-semibold text-slate-900">{{ config('app.name', 'Futuristic Africa Lab') }}</p>
                    </div>
                </a>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-2 sm:gap-3">
                        @auth
                            <a href="{{ route('client.dashboard') }}" class="client-button">Espace Collaborateur</a>
                            @if (auth()->user()->isAdmin())
                                <a href="{{ route('admin.portal') }}" class="client-button-muted">Espace admin</a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="client-button-muted">Connexion</a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="client-button">Creer un compte</a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        <main class="relative pb-20">
            <section class="client-shell pb-12 pt-16 lg:pt-24">
                <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">
                    <div class="space-y-6 fade-up" style="animation-delay: 80ms;">
                        <span class="client-badge">Interface de gestion nouvelle generation</span>

                        <h1 class="text-balance text-4xl font-semibold leading-tight sm:text-5xl lg:text-6xl client-heading-accent">
                            Suivez vos projets, vos demandes et vos echeances en un seul endroit.
                        </h1>

                        <p class="max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">
                            Ce portail collaboratif centralise toutes les interactions avec ton equipe: progression des travaux, prochaines actions, validation rapide et communication fluide.
                        </p>

                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            @auth
                                <a href="{{ route('client.dashboard') }}" class="client-button">Ouvrir l'espace Collaborateur</a>
                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('admin.portal') }}" class="client-button-muted">Ouvrir l'espace admin</a>
                                @endif
                            @else
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="client-button">Demarrer maintenant</a>
                                @endif
                                <a href="{{ route('login') }}" class="client-button-muted">J'ai deja un compte</a>
                            @endauth
                        </div>
                    </div>

                    <div class="fade-up" style="animation-delay: 180ms;">
                        <div class="client-panel client-grid-bg p-6 sm:p-8">
                            <div class="mb-8 flex items-center justify-between">
                                <p class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Vue rapide client</p>
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Synchronise</span>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <article class="client-stat">
                                    <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Demandes traitees</p>
                                    <p class="mt-3 text-3xl font-semibold text-slate-900">24</p>
                                    <p class="mt-1 text-sm text-emerald-700">+18% ce mois</p>
                                </article>
                                <article class="client-stat">
                                    <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Dossiers actifs</p>
                                    <p class="mt-3 text-3xl font-semibold text-slate-900">7</p>
                                    <p class="mt-1 text-sm text-amber-700">3 a valider</p>
                                </article>
                            </div>

                            <div class="mt-6 rounded-2xl border border-[var(--client-line)] bg-white p-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-slate-700">Avancement global</span>
                                    <span class="font-semibold text-[var(--client-accent)]">78%</span>
                                </div>
                                <div class="mt-3 h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full" style="width: 78%; background: linear-gradient(135deg, #d8602a 0%, #0f766e 100%);"></div>
                                </div>
                                <p class="mt-3 text-sm text-slate-500">Derniere mise a jour: aujourd'hui a 15:42</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="client-shell py-8">
                <div class="grid gap-5 md:grid-cols-3">
                    <article class="client-panel p-6 fade-up" style="animation-delay: 120ms;">
                        <p class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Pilotage</p>
                        <h2 class="mt-3 text-xl font-semibold text-slate-900">Tableaux de bord clairs</h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">Visualise rapidement l'etat de chaque demande, l'historique d'avancement et les points bloquants.</p>
                    </article>

                    <article class="client-panel p-6 fade-up" style="animation-delay: 180ms;">
                        <p class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Communication</p>
                        <h2 class="mt-3 text-xl font-semibold text-slate-900">Echanges centralises</h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">Tous les messages, documents et validations sont reunis dans un flux unique facile a suivre.</p>
                    </article>

                    <article class="client-panel p-6 fade-up" style="animation-delay: 240ms;">
                        <p class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Visibilite</p>
                        <h2 class="mt-3 text-xl font-semibold text-slate-900">Echeances maitrisees</h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">Recevez les rappels utiles et validez les etapes critiques sans friction, sur mobile comme desktop.</p>
                    </article>
                </div>
            </section>
        </main>

        <footer class="border-t border-[var(--client-line)] bg-white/75 py-6">
            <div class="client-shell flex flex-col gap-2 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <p>{{ config('app.name', 'FAL') }} - Gestion des  Projets</p>
                <p>Concu pour une experience claire, rapide et orientee metier.</p>
            </div>
        </footer>
    </body>
</html>
