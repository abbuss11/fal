<x-app-layout>
    @php
        $pageTasks = $tasks->getCollection();
        $canCreateTask = $canCreateTask ?? false;
        $canUpdateTask = $canUpdateTask ?? false;

        $taskSnapshots = $pageTasks->map(static function ($task) use ($statuses, $canUpdateTask): array {
            $status = (string) $task->status;
            $statusLabel = (string) ($statuses[$status] ?? strtoupper($status));
            $statusTone = match ($status) {
                'done' => 'done',
                'doing' => 'doing',
                default => 'todo',
            };

            $subtasksDone = (int) ($task->subtasks_done_count ?? 0);
            $subtasksTotal = (int) ($task->subtasks_count ?? 0);
            $subtasksProgress = $subtasksTotal > 0 ? (int) round(($subtasksDone / $subtasksTotal) * 100) : 0;

            $isLate = $task->due_date && $task->due_date->isPast() && $status !== 'done';

            return [
                'id' => (int) $task->id,
                'title' => (string) $task->title,
                'project' => (string) ($task->project?->name ?? 'Non defini'),
                'assignee' => (string) ($task->assignee?->name ?? 'Non assigne'),
                'status' => $status,
                'status_label' => $statusLabel,
                'tone' => $statusTone,
                'subtasks_done' => $subtasksDone,
                'subtasks_total' => $subtasksTotal,
                'subtasks_progress' => $subtasksProgress,
                'due_label' => $task->due_date ? $task->due_date->format('d/m/Y H:i') : 'Aucune echeance',
                'due_timestamp' => $task->due_date?->timestamp ?? 0,
                'is_late' => $isLate,
                'workspace_url' => $task->project_id ? route('client.projects.show', $task->project_id).'#board' : null,
                'edit_url' => $canUpdateTask ? route('client.tasks.edit', $task) : null,
            ];
        })->values();

        $doingCount = $taskSnapshots->where('tone', 'doing')->count();
        $doneCount = $taskSnapshots->where('tone', 'done')->count();
        $todoCount = $taskSnapshots->where('tone', 'todo')->count();
        $overdueCount = $taskSnapshots->where('is_late', true)->count();
        $subtasksTotal = (int) $taskSnapshots->sum('subtasks_total');
        $subtasksDone = (int) $taskSnapshots->sum('subtasks_done');
        $subtasksRate = $subtasksTotal > 0 ? (int) round(($subtasksDone / $subtasksTotal) * 100) : 0;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="fal-brand-kicker">Espace Collaborateur</p>
                <h1 class="mt-1 text-2xl font-semibold client-heading-accent">Task Control Center</h1>
                <p class="mt-1 text-sm text-slate-500">Vision SaaS complete de la file d'execution et des urgences.</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($canCreateTask)
                    <a href="{{ route('client.tasks.create', ['project_id' => $selectedProjectId]) }}" class="client-button">Nouvelle tache</a>
                @endif
                <a href="{{ route('client.tasks.calendar') }}" class="client-button-muted">Calendrier</a>
                <a href="{{ route('client.dashboard') }}" class="client-button-muted">Retour dashboard</a>
            </div>
        </div>
    </x-slot>

    <div class="client-shell space-y-5" x-data="taskControlCenter(@js($taskSnapshots->all()))">
        <section class="saas-hero">
            <div class="saas-hero-content">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <span class="client-badge">Execution cockpit</span>
                        <h2 class="mt-3 text-2xl font-semibold text-slate-900">Pilotage des taches, charge et delais</h2>
                        <p class="mt-1 text-sm text-slate-600">Suivi instantane des work items avec priorisation des retards.</p>
                    </div>
                    <div class="grid w-full max-w-3xl gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Taches visibles</p>
                            <p class="saas-kpi-value">{{ $tasks->total() }}</p>
                            <p class="saas-kpi-help">{{ $taskSnapshots->count() }} sur cette page</p>
                        </article>
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">En cours</p>
                            <p class="saas-kpi-value">{{ $doingCount }}</p>
                            <p class="saas-kpi-help">{{ $todoCount }} en backlog</p>
                        </article>
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Sous-taches</p>
                            <p class="saas-kpi-value">{{ $subtasksRate }}%</p>
                            <p class="saas-kpi-help">{{ $subtasksDone }} / {{ $subtasksTotal }} terminees</p>
                        </article>
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Retards</p>
                            <p class="saas-kpi-value">{{ $overdueCount }}</p>
                            <p class="saas-kpi-help">{{ $doneCount }} cloturees</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="client-panel p-4">
            <form method="GET" action="{{ route('client.tasks.index') }}" class="saas-command-bar">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1fr_auto]">
                    <label class="block">
                        <span class="saas-label">Recherche locale</span>
                        <input x-model.trim="query" type="text" class="saas-field" placeholder="Titre, projet, assigne ou #ID">
                    </label>
                    <label class="block">
                        <span class="saas-label">Statut (serveur)</span>
                        <select id="status" name="status" class="saas-field">
                            <option value="">Tous</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="saas-label">Projet (serveur)</span>
                        <select id="project_id" name="project_id" class="saas-field">
                            <option value="">Tous</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected($selectedProjectId === $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="saas-label">Tag (serveur)</span>
                        <select id="tag_id" name="tag_id" class="saas-field">
                            <option value="">Tous</option>
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}" @selected($selectedTagId === $tag->id)>{{ $tag->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="client-button">Filtrer</button>
                        <a href="{{ route('client.tasks.index') }}" class="client-button-muted">Reset</a>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="portfolio-toggle" :class="viewMode === 'cards' ? 'portfolio-toggle-active' : ''" @click="viewMode = 'cards'">Cards</button>
                        <button type="button" class="portfolio-toggle" :class="viewMode === 'table' ? 'portfolio-toggle-active' : ''" @click="viewMode = 'table'">Table</button>
                        <button type="button" class="portfolio-toggle" :class="lateOnly ? 'portfolio-toggle-active' : ''" @click="lateOnly = !lateOnly">Retards seulement</button>
                    </div>
                    <p class="text-xs text-slate-500"><strong class="text-slate-700" x-text="filteredTasks.length"></strong> taches visibles apres filtre local.</p>
                </div>
            </form>
        </section>

        <section x-cloak x-show="filteredTasks.length === 0">
            <div class="saas-empty text-center">
                Aucun element pour ce filtre.
            </div>
        </section>

        <section x-cloak x-show="filteredTasks.length > 0 && viewMode === 'cards'">
            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <template x-for="task in filteredTasks" :key="`task-card-${task.id}`">
                    <article class="saas-panel">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">Task #<span x-text="task.id"></span></p>
                                <h3 class="mt-1 text-base font-semibold text-slate-900" x-text="task.title"></h3>
                                <p class="mt-1 text-xs text-slate-500"><span x-text="task.project"></span> | <span x-text="task.assignee"></span></p>
                            </div>
                            <span
                                class="fal-status-pill"
                                :class="{
                                    'fal-status-pill-done': task.tone === 'done',
                                    'fal-status-pill-doing': task.tone === 'doing',
                                    'fal-status-pill-todo': task.tone === 'todo',
                                }"
                                x-text="task.status_label"
                            ></span>
                        </div>

                        <div class="mt-3 rounded-xl border border-[var(--client-line)] bg-slate-50 p-3">
                            <div class="flex items-center justify-between text-xs text-slate-600">
                                <span>Sous-taches</span>
                                <span x-text="task.subtasks_done + ' / ' + task.subtasks_total"></span>
                            </div>
                            <div class="mt-1.5 h-1.5 rounded-full bg-slate-200">
                                <div class="h-1.5 rounded-full bg-gradient-to-r from-[var(--client-accent)] to-[var(--client-accent-soft)]" :style="`width: ${task.subtasks_progress}%;`"></div>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="task.is_late ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700'"
                                x-text="task.due_label"
                            ></span>
                            <div class="flex items-center gap-3">
                                <a x-show="task.workspace_url" :href="task.workspace_url" class="text-sm font-semibold text-[var(--client-accent)] hover:text-cyan-700">Workspace</a>
                                <a x-show="task.edit_url" :href="task.edit_url" class="text-sm font-semibold text-slate-700 hover:text-slate-900">Modifier</a>
                            </div>
                        </div>
                    </article>
                </template>
            </div>
        </section>

        <section x-cloak x-show="filteredTasks.length > 0 && viewMode === 'table'" class="saas-table">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--client-line)]">
                    <thead class="saas-table-head">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tache</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Projet</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Assigne</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Sous-taches</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Echeance</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--client-line)] bg-white">
                        <template x-for="task in filteredTasks" :key="`task-row-${task.id}`">
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                    <p x-text="task.title"></p>
                                    <p class="mt-1 text-xs text-slate-500">Ref #<span x-text="task.id"></span></p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600" x-text="task.project"></td>
                                <td class="px-4 py-3 text-sm text-slate-600" x-text="task.assignee"></td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <span
                                        class="fal-status-pill"
                                        :class="{
                                            'fal-status-pill-done': task.tone === 'done',
                                            'fal-status-pill-doing': task.tone === 'doing',
                                            'fal-status-pill-todo': task.tone === 'todo',
                                        }"
                                        x-text="task.status_label"
                                    ></span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <p><span x-text="task.subtasks_done"></span>/<span x-text="task.subtasks_total"></span> (<span x-text="task.subtasks_progress"></span>%)</p>
                                    <div class="mt-1 h-1.5 w-24 rounded-full bg-slate-200">
                                        <div class="h-1.5 rounded-full bg-gradient-to-r from-[var(--client-accent)] to-[var(--client-accent-soft)]" :style="`width: ${task.subtasks_progress}%;`"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="task.is_late ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700'"
                                        x-text="task.due_label"
                                    ></span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <a x-show="task.workspace_url" :href="task.workspace_url" class="text-sm font-semibold text-[var(--client-accent)] hover:text-cyan-700">Workspace</a>
                                        <a x-show="task.edit_url" :href="task.edit_url" class="text-sm font-semibold text-slate-700 hover:text-slate-900">Modifier</a>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>

        <div>
            {{ $tasks->links() }}
        </div>
    </div>

    <script>
        function taskControlCenter(initialTasks) {
            return {
                tasks: Array.isArray(initialTasks) ? initialTasks : [],
                query: '',
                lateOnly: false,
                viewMode: 'cards',
                get filteredTasks() {
                    const keyword = this.query.trim().toLowerCase();

                    return this.tasks.filter((task) => {
                        const haystack = `${task.title} ${task.project} ${task.assignee} ${task.id}`.toLowerCase();
                        const matchesKeyword = keyword === '' || haystack.includes(keyword);
                        const matchesLate = !this.lateOnly || task.is_late;

                        return matchesKeyword && matchesLate;
                    });
                },
            };
        }
    </script>
</x-app-layout>
