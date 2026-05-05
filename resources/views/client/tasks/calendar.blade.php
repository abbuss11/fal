<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Calendrier</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Calendrier des taches</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $month->translatedFormat('F Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('client.tasks.calendar', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="client-button-muted">Mois precedent</a>
                <a href="{{ route('client.tasks.calendar', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="client-button-muted">Mois suivant</a>
                <a href="{{ route('client.tasks.index') }}" class="client-button">Liste des taches</a>
            </div>
        </div>
    </x-slot>

    <div class="client-shell">
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7">
            @foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $label)
                <div class="hidden rounded-xl border border-[var(--client-line)] bg-white py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 lg:block">
                    {{ $label }}
                </div>
            @endforeach

            @foreach ($days as $day)
                <article class="client-panel min-h-[160px] p-3 {{ $day['is_current_month'] ? '' : 'opacity-50' }}">
                    <p class="text-sm font-semibold text-slate-900">{{ $day['date']->format('d/m') }}</p>
                    <div class="mt-2 space-y-2">
                        @forelse ($day['tasks'] as $task)
                            <a href="{{ route('client.projects.show', $task->project_id) }}#board" class="block rounded-lg border border-[var(--client-line)] bg-white px-2 py-1 text-xs text-slate-600">
                                <span class="font-semibold">{{ $task->title }}</span><br>
                                <span>{{ $statuses[$task->status] ?? strtoupper((string) $task->status) }}</span>
                            </a>
                        @empty
                            <p class="text-xs text-slate-400">Aucune tache</p>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </section>
    </div>
</x-app-layout>

