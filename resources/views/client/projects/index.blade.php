<x-app-layout>
    @php
        $pageProjects = $projects->getCollection();
        $currentUser = auth()->user();
        $canCreateProject = (bool) $currentUser?->hasPermission('projects.create');
        $canUpdateProject = (bool) $currentUser?->hasPermission('projects.update');

        $projectSnapshots = $pageProjects->map(static function ($project) use ($currentUser, $canUpdateProject): array {
            $tasksTotal = (int) $project->tasks_count;
            $tasksDone = (int) $project->tasks_done_count;
            $progress = $tasksTotal > 0 ? (int) round(($tasksDone / $tasksTotal) * 100) : 0;
            $statusRaw = strtolower((string) $project->status);

            $tone = match (true) {
                in_array($statusRaw, ['completed', 'done', 'closed'], true) => 'done',
                in_array($statusRaw, ['in_progress', 'doing', 'ongoing', 'active'], true) => 'doing',
                default => 'todo',
            };

            return [
                'id' => (int) $project->id,
                'name' => (string) $project->name,
                'owner' => (string) ($project->owner?->name ?? 'Non defini'),
                'status_label' => strtoupper((string) ($project->status ?: 'inconnu')),
                'tone' => $tone,
                'tasks_total' => $tasksTotal,
                'tasks_done' => $tasksDone,
                'tasks_open' => max($tasksTotal - $tasksDone, 0),
                'progress' => $progress,
                'updated_human' => $project->updated_at?->diffForHumans() ?? 'N/A',
                'updated_ts' => $project->updated_at?->timestamp ?? 0,
                'workspace_url' => route('client.projects.show', $project),
                'report_url' => route('client.projects.report', $project),
                'edit_url' => $canUpdateProject && $currentUser?->canManageProject($project)
                    ? route('client.projects.edit', $project)
                    : null,
            ];
        })->values();

        $activeCount = $projectSnapshots->where('tone', 'doing')->count();
        $completedCount = $projectSnapshots->where('tone', 'done')->count();
        $backlogCount = $projectSnapshots->where('tone', 'todo')->count();
        $totalTasks = (int) $projectSnapshots->sum('tasks_total');
        $doneTasks = (int) $projectSnapshots->sum('tasks_done');
        $averageProgress = $projectSnapshots->count() > 0
            ? (int) round($projectSnapshots->avg('progress'))
            : 0;
        $completionRate = $totalTasks > 0
            ? (int) round(($doneTasks / $totalTasks) * 100)
            : 0;
        $stalledCount = $projectSnapshots->filter(static function (array $project): bool {
            return $project['tasks_total'] > 0 && $project['progress'] < 35;
        })->count();

        $portfolioStats = [
            'portfolio_total' => (int) $projects->total(),
            'on_page' => (int) $projectSnapshots->count(),
            'active' => (int) $activeCount,
            'completed' => (int) $completedCount,
            'backlog' => (int) $backlogCount,
            'average_progress' => (int) $averageProgress,
            'completion_rate' => (int) $completionRate,
            'stalled' => (int) $stalledCount,
        ];
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="fal-brand-kicker">Espace Collaborateur</p>
                <h1 class="mt-1 text-2xl font-semibold client-heading-accent">Projects Portfolio Hub</h1>
                <p class="mt-1 text-sm text-slate-500">Refonte SaaS: pilotage, tri intelligent, vues operationnelles.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($canCreateProject)
                    <a href="{{ route('client.projects.create') }}" class="client-button">Nouveau projet</a>
                @endif
                <a href="{{ route('client.tasks.index') }}" class="client-button-muted">Task Center</a>
                <a href="{{ route('client.dashboard') }}" class="client-button-muted">Retour dashboard</a>
            </div>
        </div>
    </x-slot>

    <div
        class="client-shell space-y-5"
        x-data="projectPortfolioHub(@js($projectSnapshots->all()), @js($portfolioStats))"
    >

        <section class="fal-hero">
            <div class="fal-hero-content flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="client-badge">SaaS Portfolio Command</span>
                    <h2 class="mt-3 text-2xl font-semibold text-slate-900">Vision manageriale des projets et de la capacite</h2>
                    <p class="mt-1 text-sm text-slate-600">Version SaaS complete: operations, pipeline statut, priorisation et actions rapides.</p>
                </div>
                <div class="fal-kpi-grid w-full max-w-3xl md:grid-cols-4">
                    <article class="fal-kpi-card">
                        <p class="fal-kpi-label">Portfolio total</p>
                        <p class="fal-kpi-value">{{ $portfolioStats['portfolio_total'] }}</p>
                        <p class="fal-kpi-help">{{ $portfolioStats['on_page'] }} sur cette page</p>
                    </article>
                    <article class="fal-kpi-card">
                        <p class="fal-kpi-label">Execution active</p>
                        <p class="fal-kpi-value">{{ $portfolioStats['active'] }}</p>
                        <p class="fal-kpi-help">Workstreams en cours</p>
                    </article>
                    <article class="fal-kpi-card">
                        <p class="fal-kpi-label">Delivery rate</p>
                        <p class="fal-kpi-value">{{ $portfolioStats['completion_rate'] }}%</p>
                        <p class="fal-kpi-help">{{ $portfolioStats['completed'] }} projets clotures</p>
                    </article>
                    <article class="fal-kpi-card">
                        <p class="fal-kpi-label">Signal de risque</p>
                        <p class="fal-kpi-value">{{ $portfolioStats['stalled'] }}</p>
                        <p class="fal-kpi-help">Progression &lt; 35%</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="client-panel p-4">
            <div class="portfolio-command-shell p-3">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1.2fr_0.6fr_0.6fr_auto]">
                    <label class="block">
                        <span class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.11em] text-slate-500">Recherche</span>
                        <input
                            x-model.trim="query"
                            type="text"
                            class="portfolio-field"
                            placeholder="Projet, owner ou #ID (page courante)"
                        >
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.11em] text-slate-500">Flux statut</span>
                        <select x-model="statusFilter" class="portfolio-field">
                            <option value="all">Tous les statuts</option>
                            <option value="doing">En execution</option>
                            <option value="done">Clotures</option>
                            <option value="todo">Backlog</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.11em] text-slate-500">Tri</span>
                        <select x-model="sortBy" class="portfolio-field">
                            <option value="recent">Plus recents</option>
                            <option value="progress_desc">Progression haute</option>
                            <option value="progress_asc">Progression basse</option>
                            <option value="workload_desc">Charge ouverte</option>
                            <option value="name_asc">Nom A-Z</option>
                        </select>
                    </label>

                    <div class="flex items-end gap-2">
                        <button type="button" class="portfolio-toggle" :class="viewMode === 'cards' ? 'portfolio-toggle-active' : ''" @click="viewMode = 'cards'">
                            Cards
                        </button>
                        <button type="button" class="portfolio-toggle" :class="viewMode === 'table' ? 'portfolio-toggle-active' : ''" @click="viewMode = 'table'">
                            Table
                        </button>
                    </div>
                </div>

                <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                    <p>Filtres appliques sur la page courante.</p>
                    <p><strong class="text-slate-700" x-text="filteredProjects.length"></strong> projets visibles apres filtrage.</p>
                </div>
            </div>
        </section>

        <section class="grid gap-3 md:grid-cols-3">
            <article class="portfolio-stage-card">
                <p class="portfolio-stage-label">Backlog</p>
                <div class="mt-2 flex items-end justify-between">
                    <p class="text-3xl font-semibold text-slate-900" x-text="countByTone('todo')"></p>
                    <p class="text-xs font-semibold text-slate-500" x-text="percentByTone('todo') + '%'"></p>
                </div>
                <div class="portfolio-stage-progress">
                    <span class="bg-gradient-to-r from-sky-500 to-cyan-500" :style="`width: ${percentByTone('todo')}%;`"></span>
                </div>
            </article>

            <article class="portfolio-stage-card">
                <p class="portfolio-stage-label">En execution</p>
                <div class="mt-2 flex items-end justify-between">
                    <p class="text-3xl font-semibold text-slate-900" x-text="countByTone('doing')"></p>
                    <p class="text-xs font-semibold text-slate-500" x-text="percentByTone('doing') + '%'"></p>
                </div>
                <div class="portfolio-stage-progress">
                    <span class="bg-gradient-to-r from-[var(--client-accent)] to-[var(--client-accent-soft)]" :style="`width: ${percentByTone('doing')}%;`"></span>
                </div>
            </article>

            <article class="portfolio-stage-card">
                <p class="portfolio-stage-label">Livres</p>
                <div class="mt-2 flex items-end justify-between">
                    <p class="text-3xl font-semibold text-slate-900" x-text="countByTone('done')"></p>
                    <p class="text-xs font-semibold text-slate-500" x-text="percentByTone('done') + '%'"></p>
                </div>
                <div class="portfolio-stage-progress">
                    <span class="bg-gradient-to-r from-emerald-500 to-teal-500" :style="`width: ${percentByTone('done')}%;`"></span>
                </div>
            </article>
        </section>

        <section x-cloak x-show="filteredProjects.length === 0">
            <div class="portfolio-empty">
                <p class="text-base font-semibold text-slate-900">Aucun projet ne correspond a ce filtre.</p>
                <p class="mt-1 text-sm text-slate-500">Change la recherche ou le statut pour retrouver ton portefeuille.</p>
            </div>
        </section>

        <section x-cloak x-show="filteredProjects.length > 0 && viewMode === 'cards'">
            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <template x-for="project in filteredProjects" :key="`card-${project.id}`">
                    <article class="portfolio-card">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">Projet #<span x-text="project.id"></span></p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-900" x-text="project.name"></h3>
                                <p class="mt-1 text-sm text-slate-500">Owner: <span x-text="project.owner"></span></p>
                            </div>
                            <span
                                class="fal-status-pill"
                                :class="{
                                    'fal-status-pill-done': project.tone === 'done',
                                    'fal-status-pill-doing': project.tone === 'doing',
                                    'fal-status-pill-todo': project.tone === 'todo',
                                }"
                                x-text="project.status_label"
                            ></span>
                        </div>

                        <div class="mt-4 grid grid-cols-[4.8rem_1fr] items-center gap-3">
                            <div class="portfolio-ring" :style="`--progress: ${project.progress};`">
                                <span x-text="project.progress + '%'"></span>
                            </div>
                            <div>
                                <div class="flex items-center justify-between text-xs text-slate-500">
                                    <span>Execution</span>
                                    <span x-text="project.tasks_done + ' / ' + project.tasks_total + ' tasks'"></span>
                                </div>
                                <div class="mt-2 h-1.5 rounded-full bg-slate-200">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-[var(--client-accent)] to-[var(--client-accent-soft)]" :style="`width: ${project.progress}%;`"></div>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">
                                    <span x-text="project.tasks_open"></span> ouvertes | MAJ <span x-text="project.updated_human"></span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center gap-2">
                            <a :href="project.workspace_url" class="client-button !px-3 !py-2">Workspace</a>
                            <a :href="project.report_url" class="client-button-muted !px-3 !py-2">Rapport</a>
                            <a x-show="project.edit_url" :href="project.edit_url" class="client-button-muted !px-3 !py-2">Modifier</a>
                        </div>
                    </article>
                </template>
            </div>
        </section>

        <section x-cloak x-show="filteredProjects.length > 0 && viewMode === 'table'" class="fal-table-shell">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--client-line)]">
                    <thead class="fal-table-head">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Projet</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Owner</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Progression</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Charge ouverte</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--client-line)] bg-white">
                        <template x-for="project in filteredProjects" :key="`row-${project.id}`">
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-900" x-text="project.name"></p>
                                    <p class="mt-1 text-xs text-slate-500">Projet #<span x-text="project.id"></span> | MAJ <span x-text="project.updated_human"></span></p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600" x-text="project.owner"></td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <span
                                        class="fal-status-pill"
                                        :class="{
                                            'fal-status-pill-done': project.tone === 'done',
                                            'fal-status-pill-doing': project.tone === 'doing',
                                            'fal-status-pill-todo': project.tone === 'todo',
                                        }"
                                        x-text="project.status_label"
                                    ></span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <p><span x-text="project.tasks_done"></span> / <span x-text="project.tasks_total"></span> (<span x-text="project.progress"></span>%)</p>
                                    <div class="mt-1 h-1.5 w-28 rounded-full bg-slate-200">
                                        <div class="h-1.5 rounded-full bg-gradient-to-r from-[var(--client-accent)] to-[var(--client-accent-soft)]" :style="`width: ${project.progress}%;`"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <span x-text="project.tasks_open"></span> taches
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <a :href="project.workspace_url" class="text-sm font-semibold text-[var(--client-accent)] hover:text-cyan-700">Workspace</a>
                                        <a :href="project.report_url" class="text-sm font-semibold text-cyan-700 hover:text-cyan-600">Rapport</a>
                                        <a x-show="project.edit_url" :href="project.edit_url" class="text-sm font-semibold text-slate-700 hover:text-slate-900">Modifier</a>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="mt-4">
            {{ $projects->links() }}
        </div>
    </div>

    <script>
        function projectPortfolioHub(initialProjects, initialStats) {
            return {
                projects: Array.isArray(initialProjects) ? initialProjects : [],
                stats: initialStats ?? {},
                query: '',
                statusFilter: 'all',
                sortBy: 'recent',
                viewMode: 'cards',
                get filteredProjects() {
                    const keyword = this.query.trim().toLowerCase();
                    const rows = this.projects.filter((project) => {
                        const name = String(project.name ?? '').toLowerCase();
                        const owner = String(project.owner ?? '').toLowerCase();
                        const id = String(project.id ?? '');
                        const matchesKeyword = keyword === '' || name.includes(keyword) || owner.includes(keyword) || id.includes(keyword);
                        const matchesStatus = this.statusFilter === 'all' || project.tone === this.statusFilter;

                        return matchesKeyword && matchesStatus;
                    });

                    return [...rows].sort((a, b) => {
                        if (this.sortBy === 'progress_desc') {
                            return Number(b.progress ?? 0) - Number(a.progress ?? 0);
                        }
                        if (this.sortBy === 'progress_asc') {
                            return Number(a.progress ?? 0) - Number(b.progress ?? 0);
                        }
                        if (this.sortBy === 'workload_desc') {
                            return Number(b.tasks_open ?? 0) - Number(a.tasks_open ?? 0);
                        }
                        if (this.sortBy === 'name_asc') {
                            return String(a.name ?? '').localeCompare(String(b.name ?? ''), 'fr');
                        }

                        return Number(b.updated_ts ?? 0) - Number(a.updated_ts ?? 0);
                    });
                },
                countByTone(tone) {
                    return this.filteredProjects.filter((project) => project.tone === tone).length;
                },
                percentByTone(tone) {
                    if (this.filteredProjects.length === 0) {
                        return 0;
                    }

                    return Math.round((this.countByTone(tone) / this.filteredProjects.length) * 100);
                },
            };
        }
    </script>
</x-app-layout>
