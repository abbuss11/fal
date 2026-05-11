<x-app-layout>
    @php
        $allTasks = collect($days)->flatMap(static function (array $day) {
            return $day['tasks'];
        });

        $totalScheduled = $allTasks->count();
        $doneScheduled = $allTasks->where('status', \App\Models\Task::STATUS_DONE)->count();
        $overdueScheduled = $allTasks->filter(static function ($task): bool {
            return $task->due_date && $task->due_date->isPast() && $task->status !== \App\Models\Task::STATUS_DONE;
        })->count();
        $peakDay = collect($days)
            ->sortByDesc(static fn (array $day) => $day['tasks']->count())
            ->first();
        $peakDayLabel = $peakDay ? $peakDay['date']->format('d/m') : '-';
        $peakDayVolume = $peakDay ? $peakDay['tasks']->count() : 0;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="fal-brand-kicker">Planning intelligence</p>
                <h1 class="mt-1 text-2xl font-semibold client-heading-accent">Calendrier des taches</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $month->translatedFormat('F Y') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('client.tasks.calendar', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="client-button-muted">Mois precedent</a>
                <a href="{{ route('client.tasks.calendar', ['month' => now()->format('Y-m')]) }}" class="client-button-muted">Mois courant</a>
                <a href="{{ route('client.tasks.calendar', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="client-button-muted">Mois suivant</a>
                <a href="{{ route('client.tasks.index') }}" class="client-button">Liste des taches</a>
            </div>
        </div>
    </x-slot>

    <div class="client-shell space-y-5">
        <section class="saas-hero">
            <div class="saas-hero-content grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Taches planifiees</p>
                    <p class="saas-kpi-value">{{ $totalScheduled }}</p>
                    <p class="saas-kpi-help">Periode affichee</p>
                </article>
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Terminees</p>
                    <p class="saas-kpi-value">{{ $doneScheduled }}</p>
                    <p class="saas-kpi-help">Dans cette grille</p>
                </article>
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Retards</p>
                    <p class="saas-kpi-value">{{ $overdueScheduled }}</p>
                    <p class="saas-kpi-help">Action immediate</p>
                </article>
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Pic d'activite</p>
                    <p class="saas-kpi-value">{{ $peakDayVolume }}</p>
                    <p class="saas-kpi-help">Jour: {{ $peakDayLabel }}</p>
                </article>
            </div>
        </section>

        <section class="client-panel p-4">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">To do</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-cyan-100 px-2.5 py-1 font-semibold text-cyan-700">Doing</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-700">Done</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-1 font-semibold text-rose-700">Retard</span>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7">
            @foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $label)
                <div class="hidden rounded-xl border border-[var(--client-line)] bg-white py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 lg:block">
                    {{ $label }}
                </div>
            @endforeach

            @foreach ($days as $day)
                @php
                    $isToday = $day['date']->isSameDay(now());
                @endphp
                <article class="saas-panel min-h-[180px] p-3 {{ $day['is_current_month'] ? '' : 'opacity-55' }} {{ $isToday ? 'ring-2 ring-cyan-200' : '' }}">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900">{{ $day['date']->format('d/m') }}</p>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">
                            {{ $day['tasks']->count() }}
                        </span>
                    </div>

                    <div class="space-y-2">
                        @forelse ($day['tasks'] as $task)
                            @php
                                $statusColor = match ((string) $task->status) {
                                    \App\Models\Task::STATUS_DONE => 'bg-emerald-100 text-emerald-700',
                                    \App\Models\Task::STATUS_DOING => 'bg-cyan-100 text-cyan-700',
                                    default => 'bg-slate-100 text-slate-600',
                                };
                                $isLate = $task->due_date && $task->due_date->isPast() && $task->status !== \App\Models\Task::STATUS_DONE;
                            @endphp
                            <a href="{{ route('client.projects.show', $task->project_id) }}#board" class="block rounded-lg border border-[var(--client-line)] bg-white px-2.5 py-2 text-xs text-slate-600 transition hover:border-cyan-300">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="font-semibold text-slate-800">{{ $task->title }}</span>
                                    <span class="inline-flex shrink-0 rounded-full px-2 py-0.5 font-semibold {{ $isLate ? 'bg-rose-100 text-rose-700' : $statusColor }}">
                                        {{ $isLate ? 'Retard' : ($statuses[$task->status] ?? strtoupper((string) $task->status)) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-[11px] text-slate-500">{{ $task->project?->name ?? 'Projet' }}</p>
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
