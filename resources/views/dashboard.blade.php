@php
    $stats = [
        ['label' => 'Demandes ouvertes', 'value' => 12, 'delta' => '+3 cette semaine', 'tone' => 'text-emerald-700'],
        ['label' => 'Actions a valider', 'value' => 4, 'delta' => '2 urgentes', 'tone' => 'text-amber-700'],
        ['label' => 'Echeances proches', 'value' => 6, 'delta' => 'Sous 7 jours', 'tone' => 'text-orange-700'],
        ['label' => 'Messages non lus', 'value' => 5, 'delta' => 'Dernier il y a 20 min', 'tone' => 'text-sky-700'],
    ];

    $projects = [
        ['name' => 'Mise a jour portail RH', 'owner' => 'Equipe Produit', 'progress' => 82, 'status' => 'En bonne voie'],
        ['name' => 'Refonte support client', 'owner' => 'Equipe UX', 'progress' => 64, 'status' => 'A valider'],
        ['name' => 'Automatisation reporting', 'owner' => 'Equipe Data', 'progress' => 48, 'status' => 'En cours'],
    ];

    $timeline = [
        ['time' => '09:30', 'title' => 'Revue sprint client', 'note' => 'Point avec l equipe projet'],
        ['time' => '12:00', 'title' => 'Validation maquette', 'note' => 'Confirmer la version mobile'],
        ['time' => '15:45', 'title' => 'Envoi livrable', 'note' => 'Partager le rapport hebdomadaire'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Tableau de bord client</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Bienvenue, {{ auth()->user()->name }}</h1>
            </div>
            <p class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                Derniere connexion: {{ now()->format('d/m/Y H:i') }}
            </p>
        </div>
    </x-slot>

    <div class="client-shell space-y-8">
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stats as $index => $item)
                <article class="client-stat fade-up" style="animation-delay: {{ 100 + ($index * 60) }}ms;">
                    <p class="text-xs uppercase tracking-[0.12em] text-slate-500">{{ $item['label'] }}</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $item['value'] }}</p>
                    <p class="mt-2 text-sm font-medium {{ $item['tone'] }}">{{ $item['delta'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
            <article class="client-panel p-6 sm:p-8 fade-up" style="animation-delay: 120ms;">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-slate-900">Dossiers en cours</h2>
                    <a href="{{ route('profile.edit') }}" class="text-sm font-semibold text-[var(--client-accent)] hover:text-orange-700">Gerer mon profil</a>
                </div>

                <div class="space-y-5">
                    @foreach ($projects as $project)
                        <div class="rounded-2xl border border-[var(--client-line)] bg-white p-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $project['name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $project['owner'] }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-[var(--client-accent)]">
                                    {{ $project['status'] }}
                                </span>
                            </div>

                            <div class="mt-3 h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full" style="width: {{ $project['progress'] }}%; background: linear-gradient(90deg, #d8602a 0%, #0f766e 100%);"></div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">Progression: {{ $project['progress'] }}%</p>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="client-panel p-6 fade-up client-grid-bg" style="animation-delay: 180ms;">
                <h2 class="text-xl font-semibold text-slate-900">Agenda du jour</h2>
                <p class="mt-1 text-sm text-slate-500">Actions prioritaires a suivre</p>

                <div class="mt-5 space-y-4">
                    @foreach ($timeline as $event)
                        <div class="rounded-xl border border-[var(--client-line)] bg-white px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-900">{{ $event['title'] }}</p>
                                <span class="text-xs font-semibold text-[var(--client-accent)]">{{ $event['time'] }}</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $event['note'] }}</p>
                        </div>
                    @endforeach
                </div>

                <a href="{{ route('profile.edit') }}" class="client-button mt-6 w-full">Mettre a jour mes informations</a>
            </article>
        </section>
    </div>
</x-app-layout>
