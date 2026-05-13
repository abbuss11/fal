<x-filament-panels::page>
    @php
        $allTasks = collect($this->calendarDays)->flatMap(static function (array $day) {
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
        $peakDay = collect($this->calendarDays)
            ->sortByDesc(static fn (array $day) => $day['tasks']->count())
            ->first();
        $peakDayCount = $peakDay ? $peakDay['tasks']->count() : 0;
        $peakDayLabel = $peakDay ? $peakDay['date']->format('d/m') : '-';
        try {
            $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $this->month)->translatedFormat('F Y');
        } catch (\Throwable) {
            $monthLabel = $this->month;
        }
    @endphp

    <div class="space-y-5" wire:poll.20s>
        <section class="rounded-2xl border border-gray-200 bg-gradient-to-br from-white via-orange-50/40 to-cyan-50/55 p-4 dark:border-white/10 dark:from-gray-900 dark:via-gray-900 dark:to-gray-950">
            <div class="grid gap-4 xl:grid-cols-[1fr_auto] xl:items-end">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.13em] text-gray-500 dark:text-gray-400">Filament calendar</p>
                    <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">Vue mensuelle des echeances</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        {{ $monthLabel }} | {{ $completionRate }}% finalisees
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-xl border border-gray-200 bg-white/90 p-3 dark:border-white/10 dark:bg-gray-900/80">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Planifiees</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $totalScheduled }}</p>
                    </article>
                    <article class="rounded-xl border border-gray-200 bg-white/90 p-3 dark:border-white/10 dark:bg-gray-900/80">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Done</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $doneScheduled }}</p>
                    </article>
                    <article class="rounded-xl border border-gray-200 bg-white/90 p-3 dark:border-white/10 dark:bg-gray-900/80">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Retards</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $overdueScheduled }}</p>
                    </article>
                    <article class="rounded-xl border border-gray-200 bg-white/90 p-3 dark:border-white/10 dark:bg-gray-900/80">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Pic</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $peakDayCount }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $peakDayLabel }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1fr_1fr_auto]">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Mois</label>
                    <input
                        type="month"
                        wire:model.live="month"
                        class="block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Projet</label>
                    <select
                        wire:model.live="projectId"
                        class="block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900"
                    >
                        <option value="">Tous les projets</option>
                        @foreach ($this->projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-wrap items-end gap-2 text-xs">
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600 dark:bg-gray-800 dark:text-gray-300">To do: {{ $todoScheduled }}</span>
                    <span class="rounded-full bg-cyan-100 px-2.5 py-1 font-semibold text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">Doing: {{ $doingScheduled }}</span>
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Done: {{ $doneScheduled }}</span>
                </div>
            </div>
            <div class="mt-3">
                <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.11em] text-gray-500 dark:text-gray-400">
                    <span>Progression</span>
                    <span>{{ $completionRate }}%</span>
                </div>
                <div class="mt-1.5 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-1.5 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500" style="width: {{ $completionRate }}%;"></div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-slate-50/60 p-3 dark:border-white/10 dark:bg-gray-950/35">
            <div class="hidden gap-2 lg:grid lg:grid-cols-7">
                @foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $dayName)
                    <div class="rounded-lg border border-gray-200 bg-white py-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-white/10 dark:bg-gray-900 dark:text-gray-300">
                        {{ $dayName }}
                    </div>
                @endforeach
            </div>

            <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7">
                @foreach ($this->calendarDays as $day)
                    @php
                        $dayCount = $day['tasks']->count();
                        $dayLoad = $peakDayCount > 0 ? (int) round(($dayCount / $peakDayCount) * 100) : 0;
                    @endphp
                    <article class="min-h-[190px] rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition hover:border-orange-300 hover:shadow-md {{ $day['is_current_month'] ? '' : 'opacity-55' }} dark:border-white/10 dark:bg-gray-900 dark:hover:border-orange-400/50">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">{{ $day['date']->translatedFormat('D') }}</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $day['date']->format('d/m') }}</p>
                            </div>
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:bg-gray-800 dark:text-gray-300">
                                {{ $dayCount }}
                            </span>
                        </div>

                        <div class="mt-2 h-1 rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-1 rounded-full bg-gradient-to-r from-primary-500 to-cyan-500" style="width: {{ $dayLoad }}%;"></div>
                        </div>

                        <div class="mt-3 space-y-2">
                            @forelse ($day['tasks'] as $task)
                                @php
                                    $isLate = $task->due_date && $task->due_date->isPast() && $task->status !== \App\Models\Task::STATUS_DONE;
                                    $badgeClass = match ((string) $task->status) {
                                        \App\Models\Task::STATUS_DONE => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                        \App\Models\Task::STATUS_DOING => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300',
                                        default => 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-300',
                                    };
                                @endphp
                                <a href="{{ \App\Filament\Resources\TaskResource::getUrl('edit', ['record' => $task->id]) }}" class="block rounded-lg border border-gray-200 bg-white px-2.5 py-2 text-xs transition hover:border-primary-300 dark:border-white/10 dark:bg-gray-900 dark:hover:border-primary-500">
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $task->title }}</span>
                                        <span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $isLate ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : $badgeClass }}">
                                            {{ $isLate ? 'Retard' : (\App\Models\Task::statusOptions()[$task->status] ?? strtoupper((string) $task->status)) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ $task->project?->name ?? 'Projet' }}</p>
                                    <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ $task->assignee?->name ?? 'Non assigne' }} | {{ $task->due_date?->format('H:i') ?? '--:--' }}
                                    </p>
                                </a>
                            @empty
                                <p class="rounded-lg border border-dashed border-gray-300 bg-white px-3 py-2 text-center text-xs text-gray-400 dark:border-white/15 dark:bg-gray-900 dark:text-gray-500">
                                    Aucune tache
                                </p>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-panels::page>
