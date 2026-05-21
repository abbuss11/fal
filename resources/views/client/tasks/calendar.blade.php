<x-app-layout>
    @php
        $allTasks = collect($days)->flatMap(static function (array $day) {
            return $day['tasks'];
        });

        $totalScheduled = $allTasks->count();
        $doneScheduled = $allTasks->where('status', \App\Models\Task::STATUS_DONE)->count();
        $doingScheduled = $allTasks->where('status', \App\Models\Task::STATUS_DOING)->count();
        $todoScheduled = max($totalScheduled - $doneScheduled - $doingScheduled, 0);
        $overdueScheduled = $allTasks->filter(static function ($task): bool {
            return $task->due_date && $task->due_date->isPast() && $task->status !== \App\Models\Task::STATUS_DONE;
        })->count();
        $completionRate = $totalScheduled > 0 ? (int) round(($doneScheduled / $totalScheduled) * 100) : 0;
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
            <div class="saas-hero-content">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <span class="client-badge">Calendar cockpit</span>
                        <h2 class="mt-3 text-2xl font-semibold text-slate-900">Vue mensuelle des echeances et priorites</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $month->translatedFormat('F Y') }} - {{ $completionRate }}% des taches programmees sont deja finalisees.
                        </p>
                    </div>
                    <div class="grid w-full max-w-3xl gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Taches planifiees</p>
                            <p class="saas-kpi-value">{{ $totalScheduled }}</p>
                            <p class="saas-kpi-help">Periode affichee</p>
                        </article>
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Terminees</p>
                            <p class="saas-kpi-value">{{ $doneScheduled }}</p>
                            <p class="saas-kpi-help">{{ $completionRate }}% de completion</p>
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
                </div>
            </div>
        </section>

        <section class="client-panel p-4">
            <div class="saas-command-bar">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">To do</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-cyan-100 px-2.5 py-1 font-semibold text-cyan-700">Doing</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-700">Done</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-1 font-semibold text-rose-700">Retard</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                        <span class="rounded-lg bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">To do: {{ $todoScheduled }}</span>
                        <span class="rounded-lg bg-cyan-100 px-2.5 py-1 font-semibold text-cyan-700">Doing: {{ $doingScheduled }}</span>
                        <span class="rounded-lg bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-700">Done: {{ $doneScheduled }}</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.11em] text-slate-500">
                        <span>Progression mensuelle</span>
                        <span>{{ $completionRate }}%</span>
                    </div>
                    <div class="mt-1.5 h-1.5 rounded-full bg-slate-200">
                        <div class="h-1.5 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500 transition-all" style="width: {{ $completionRate }}%;"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="calendar-grid-shell">
            <div class="hidden gap-2 lg:grid lg:grid-cols-7">
                @foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $label)
                    <div class="calendar-weekday-chip">
                        {{ $label }}
                    </div>
                @endforeach
            </div>

            <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7">
                @foreach ($days as $day)
                    @php
                        $isToday = $day['date']->isSameDay(now());
                        $dayTaskCount = $day['tasks']->count();
                        $dayLoad = $peakDayVolume > 0 ? (int) round(($dayTaskCount / $peakDayVolume) * 100) : 0;
                    @endphp
                    <article class="calendar-day-card {{ $day['is_current_month'] ? '' : 'calendar-day-card-outside' }} {{ $isToday ? 'calendar-day-card-today' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="calendar-day-week">{{ $day['date']->translatedFormat('D') }}</p>
                                <p class="calendar-day-date">{{ $day['date']->format('d/m') }}</p>
                            </div>
                            <span class="calendar-day-count">
                                {{ $dayTaskCount }}
                            </span>
                        </div>

                        <div class="mt-2 h-1 rounded-full bg-slate-200/80">
                            <div class="h-1 rounded-full bg-gradient-to-r from-[var(--client-accent)] to-[var(--client-accent-soft)]" style="width: {{ $dayLoad }}%;"></div>
                        </div>

                        <div class="mt-3 space-y-2">
                            @forelse ($day['tasks'] as $task)
                                @php
                                    $statusColor = match ((string) $task->status) {
                                        \App\Models\Task::STATUS_DONE => 'fal-status-pill fal-status-pill-done',
                                        \App\Models\Task::STATUS_DOING => 'fal-status-pill fal-status-pill-doing',
                                        default => 'fal-status-pill fal-status-pill-todo',
                                    };
                                    $isLate = $task->due_date && $task->due_date->isPast() && $task->status !== \App\Models\Task::STATUS_DONE;
                                @endphp
                                <a href="{{ route('client.projects.show', $task->project_id) }}#board" class="calendar-task-card {{ $isLate ? 'calendar-task-card-late' : '' }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="calendar-task-title">{{ $task->title }}</span>
                                        <span class="inline-flex shrink-0 {{ $isLate ? 'rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-semibold text-rose-700' : $statusColor }}">
                                            {{ $isLate ? 'Retard' : ($statuses[$task->status] ?? strtoupper((string) $task->status)) }}
                                        </span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-slate-500">
                                        <span class="truncate">{{ $task->project?->name ?? 'Projet' }}</span>
                                        <span>{{ $task->due_date?->format('H:i') ?? '--:--' }}</span>
                                    </div>
                                </a>
                            @empty
                                <div class="calendar-day-empty">Aucune tache planifiee</div>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </div>

</x-app-layout>
