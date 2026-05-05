@php
    $stats = $overview['stats'];
    $statusBreakdown = $overview['status_breakdown'];
    $priorityBreakdown = $overview['priority_breakdown'];
    $timeline = $overview['timeline'];
    $memberWorkload = $overview['member_workload'];
    $velocity = $overview['velocity_last_weeks'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Space de travail</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $project->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Chef de projet: {{ $project->owner?->name ?? 'Non defini' }} |
                    Statut: {{ \App\Models\Project::statusOptions()[$project->status] ?? strtoupper((string) $project->status) }}
                </p>
                @if ($project->objective)
                    <p class="mt-1 text-sm text-slate-600">Objectif: {{ $project->objective }}</p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('client.projects.index') }}" class="client-button-muted">Retour projets</a>
                <a href="{{ route('client.tasks.index', ['project_id' => $project->id]) }}" class="client-button-muted">Tableau des taches</a>
                <a href="{{ route('client.projects.report', $project) }}" class="client-button">Rapport complet</a>
            </div>
        </div>
    </x-slot>

    <div
        class="client-shell space-y-6"
        x-data="projectWorkspace({
            projectId: @js($project->id),
            snapshotUrl: @js(route('client.projects.snapshot', $project)),
            moveTaskUrlPrefix: @js(url('/client/projects/'.$project->id.'/tasks')),
            csrfToken: @js(csrf_token()),
            snapshotVersion: @js($snapshotVersion),
            initialSnapshot: @js($liveSnapshot),
        })"
        x-init="init()"
    >
        @if (session('status'))
            <div class="client-panel border-l-4 border-l-emerald-500 p-4 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <section class="client-panel p-3">
            <div class="flex flex-wrap gap-2">
                <button type="button" class="client-tab" :class="tab === 'overview' ? 'client-tab-active' : ''" @click="tab = 'overview'">Vue Globale</button>
                <button type="button" class="client-tab" :class="tab === 'timeline' ? 'client-tab-active' : ''" @click="tab = 'timeline'">Ligne du temps</button>
                <button type="button" class="client-tab" :class="tab === 'board' ? 'client-tab-active' : ''" @click="tab = 'board'">Tableau</button>
                <button type="button" class="client-tab" :class="tab === 'calendar' ? 'client-tab-active' : ''" @click="tab = 'calendar'">Calendrier</button>
                <button type="button" class="client-tab" :class="tab === 'team' ? 'client-tab-active' : ''" @click="tab = 'team'">Equipe</button>
                <button type="button" class="client-tab" :class="tab === 'comments' ? 'client-tab-active' : ''" @click="tab = 'comments'">Commentaires</button>
                <button type="button" class="client-tab" :class="tab === 'subtasks' ? 'client-tab-active' : ''" @click="tab = 'subtasks'">Sous-taches</button>
                <button type="button" class="client-tab" :class="tab === 'chat' ? 'client-tab-active' : ''" @click="tab = 'chat'">Chat interne</button>
                <button type="button" class="client-tab" :class="tab === 'files' ? 'client-tab-active' : ''" @click="tab = 'files'">Fichiers</button>
                <button type="button" class="client-tab" :class="tab === 'report' ? 'client-tab-active' : ''" @click="tab = 'report'">Rapport</button>
            </div>
        </section>

        <section id="overview" x-show="tab === 'overview'" class="space-y-6" x-cloak>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article class="client-stat">
                    <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Progression</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900" data-live-stat="progress_rate">{{ $stats['progress_rate'] }}%</p>
                    <p class="mt-1 text-sm text-emerald-700">{{ $stats['tasks_done'] }} / {{ $stats['tasks_total'] }} taches</p>
                </article>
                <article class="client-stat">
                    <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Membres actifs</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900" data-live-stat="members_active">{{ $stats['members_active'] }}</p>
                    <p class="mt-1 text-sm text-slate-600">sur {{ $stats['members_total'] }} membres</p>
                </article>
                <article class="client-stat">
                    <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Retards</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900" data-live-stat="tasks_overdue">{{ $stats['tasks_overdue'] }}</p>
                    <p class="mt-1 text-sm text-orange-700">taches en depassement</p>
                </article>
                <article class="client-stat">
                    <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Cycle moyen</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900" data-live-stat="average_completion_hours">{{ $stats['average_completion_hours'] }}h</p>
                    <p class="mt-1 text-sm text-slate-600">delai moyen d'achevement</p>
                </article>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <article class="client-panel p-5">
                    <h3 class="text-lg font-semibold text-slate-900">Repartition par statut</h3>
                    <div class="mt-4 space-y-3">
                        @foreach ($statusLabels as $status => $label)
                            @php
                                $count = $statusBreakdown[$status] ?? 0;
                                $percent = $stats['tasks_total'] > 0 ? (int) round(($count / $stats['tasks_total']) * 100) : 0;
                            @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span>{{ $label }}</span>
                                    <span>{{ $count }} ({{ $percent }}%)</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-gradient-to-r from-[var(--client-accent)] to-[var(--client-teal)]" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="client-panel p-5">
                    <h3 class="text-lg font-semibold text-slate-900">Repartition par priorite</h3>
                    <div class="mt-4 space-y-3">
                        @foreach ($priorityLabels as $priority => $label)
                            @php
                                $count = $priorityBreakdown[$priority] ?? 0;
                            @endphp
                            <div class="flex items-center justify-between rounded-xl border border-[var(--client-line)] bg-white px-3 py-2 text-sm">
                                <span>{{ $label }}</span>
                                <span class="font-semibold">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>

            <article class="client-panel p-5">
                <h3 class="text-lg font-semibold text-slate-900">Vitesse de livraison (6 dernieres semaines)</h3>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    @foreach ($velocity as $item)
                        <div class="rounded-xl border border-[var(--client-line)] bg-white p-3">
                            <p class="text-xs text-slate-500">{{ $item['label'] }}</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $item['done'] }}</p>
                            <p class="text-xs text-slate-500">taches terminees</p>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section id="timeline" x-show="tab === 'timeline'" class="space-y-4" x-cloak>
            <article class="client-panel p-5">
                <h3 class="text-lg font-semibold text-slate-900">Timeline collaborative</h3>
                <div class="mt-4 space-y-3" id="timeline-list">
                    @forelse ($timeline as $item)
                        <div class="rounded-xl border border-[var(--client-line)] bg-white p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-900">{{ $item['action'] }}</p>
                                <span class="text-xs text-slate-500">{{ $item['date'] }}</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-600">Acteur: {{ $item['actor'] }}</p>
                            @if ($item['task'])
                                <p class="mt-1 text-xs text-slate-600">Tache: {{ $item['task'] }}</p>
                            @endif
                            @if (! empty($item['meta']))
                                <p class="mt-1 text-xs text-slate-500">{{ json_encode($item['meta'], JSON_UNESCAPED_UNICODE) }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-4 text-sm text-slate-500">Aucun evenement pour le moment.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section id="board" x-show="tab === 'board'" class="space-y-4" x-cloak>
            <div class="jira-board-shell">
                <aside class="jira-rail">
                    <button type="button" class="jira-rail-icon jira-rail-icon-active" aria-label="Board">
                        <span>J</span>
                    </button>
                    <button type="button" class="jira-rail-icon" aria-label="Recent">
                        <span>R</span>
                    </button>
                    <button type="button" class="jira-rail-icon" aria-label="Apps">
                        <span>A</span>
                    </button>
                    <div class="mt-auto space-y-2">
                        <button type="button" class="jira-rail-icon" aria-label="Team">
                            <span>T</span>
                        </button>
                        <button type="button" class="jira-rail-icon" aria-label="Settings">
                            <span>S</span>
                        </button>
                    </div>
                </aside>

                <aside class="jira-workspace-sidebar">
                    <div class="jira-brand">
                        <p class="jira-brand-title">Spaces</p>
                        <h3>Draco</h3>
                    </div>

                    <nav class="jira-side-nav">
                        <a href="javascript:void(0)" class="jira-side-link">For you</a>
                        <a href="javascript:void(0)" class="jira-side-link">Recent</a>
                        <a href="javascript:void(0)" class="jira-side-link">Starred</a>
                        <a href="javascript:void(0)" class="jira-side-link">Apps</a>
                        <a href="javascript:void(0)" class="jira-side-link">Plans</a>
                    </nav>

                    <div class="jira-side-block">
                        <p class="jira-side-block-title">Recent</p>
                        <a href="javascript:void(0)" class="jira-project-link jira-project-link-active">
                            <span class="jira-project-dot"></span>
                            <span>{{ $project->name }}</span>
                        </a>
                    </div>

                    <div class="jira-side-note">
                        <p>Mode <span data-board-mode-label>Kanban</span></p>
                        <p class="mt-1">Glisse une tache pour changer de colonne ou de position.</p>
                    </div>
                </aside>

                <div class="jira-workspace-main">
                    <header class="jira-topbar">
                        <label class="jira-search-wrap" for="jira-global-search">
                            <span class="jira-search-lens">Q</span>
                            <input id="jira-global-search" class="jira-search" type="text" placeholder="Search">
                        </label>

                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('client.tasks.index', ['project_id' => $project->id]) }}" class="jira-pill-btn-primary">Create</a>
                            <button type="button" class="jira-pill-btn" :class="boardMode === 'kanban' ? 'jira-pill-active' : ''" @click="setBoardMode('kanban')">Kanban</button>
                            <button type="button" class="jira-pill-btn" :class="boardMode === 'scrum' ? 'jira-pill-active' : ''" @click="setBoardMode('scrum')">Scrum</button>
                        </div>
                    </header>

                    <section class="jira-project-header">
                        <p class="jira-project-meta">Spaces</p>
                        <div class="jira-project-title-row">
                            <h2>{{ $project->name }}</h2>
                            <span class="jira-project-code">SCRUM-{{ $project->id }}</span>
                        </div>
                        <nav class="jira-project-tabs">
                            <a href="javascript:void(0)" class="jira-project-tab">Summary</a>
                            <a href="javascript:void(0)" class="jira-project-tab">Backlog</a>
                            <a href="javascript:void(0)" class="jira-project-tab jira-project-tab-active">Board</a>
                            <a href="javascript:void(0)" class="jira-project-tab">Code</a>
                            <a href="javascript:void(0)" class="jira-project-tab">Timeline</a>
                            <a href="javascript:void(0)" class="jira-project-tab">Docs</a>
                            <a href="javascript:void(0)" class="jira-project-tab">Development</a>
                        </nav>
                    </section>

                    <article class="jira-toolbar">
                        <div class="flex flex-wrap items-center gap-2">
                            <label class="jira-board-search-wrap" for="jira-board-search">
                                <span class="jira-search-lens">Q</span>
                                <input
                                    id="jira-board-search"
                                    type="text"
                                    class="jira-board-search"
                                    placeholder="Search board"
                                    x-model="boardQuery"
                                    @input.debounce.150ms="applyBoardFilter()"
                                >
                            </label>
                            <span class="jira-chip">Board</span>
                            <span class="jira-chip">Live</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" class="jira-pill-btn-primary">Complete sprint</button>
                            <button type="button" class="jira-pill-btn">Group</button>
                            <button type="button" class="jira-pill-btn">Filter</button>
                            <button type="button" class="jira-pill-btn">Display</button>
                        </div>
                    </article>

                    <article class="jira-sprint-metrics" x-show="boardMode === 'scrum'" x-cloak>
                        <div class="jira-metric-card">
                            <p>Backlog produit</p>
                            <p data-scrum-metric="backlog">0</p>
                        </div>
                        <div class="jira-metric-card">
                            <p>Sprint en cours</p>
                            <p data-scrum-metric="sprint">0</p>
                        </div>
                        <div class="jira-metric-card">
                            <p>En revue</p>
                            <p data-scrum-metric="review">0</p>
                        </div>
                        <div class="jira-metric-card">
                            <p>Terminees</p>
                            <p data-scrum-metric="done">0</p>
                        </div>
                    </article>

                    <div class="jira-board-grid">
                        @foreach ($statusOrder as $status)
                            @php
                                $scrumLabel = match ($status) {
                                    \App\Models\Task::STATUS_TODO => 'A FAIRE',
                                    \App\Models\Task::STATUS_DOING => 'EN COURS',
                                    'review' => 'EN COURS DE REVUE',
                                    \App\Models\Task::STATUS_DONE => 'TERMINE',
                                    default => strtoupper($status),
                                };
                            @endphp
                            <article class="jira-column">
                                <header class="jira-column-header">
                                    <h3
                                        data-board-title="{{ $status }}"
                                        data-kanban-label="{{ $boardStatusLabels[$status] ?? strtoupper($status) }}"
                                        data-scrum-label="{{ $scrumLabel }}"
                                    >
                                        {{ $boardStatusLabels[$status] ?? strtoupper($status) }}
                                    </h3>
                                    <span class="jira-count board-count" data-status-count="{{ $status }}">
                                        {{ count($tasksByStatus[$status] ?? []) }}
                                    </span>
                                </header>

                                <div class="space-y-3 board-column jira-column-body" data-board-column="{{ $status }}">
                                    @forelse ($tasksByStatus[$status] ?? [] as $task)
                                        @php
                                            $assigneeName = $task->assignee?->name ?? 'Non assigne';
                                            $assigneeInitial = strtoupper(substr($assigneeName, 0, 1));
                                            $doneSubtasks = $task->subtasks->where('is_completed', true)->count();
                                            $totalSubtasks = $task->subtasks->count();
                                        @endphp
                                        <article
                                            class="board-card jira-task-card"
                                            draggable="true"
                                            data-task-id="{{ $task->id }}"
                                            data-task-title="{{ $task->title }}"
                                        >
                                            <p class="jira-task-title">{{ $task->title }}</p>
                                            <p class="jira-task-date">{{ $task->due_date?->format('M d, Y') ?? 'Aucune date' }}</p>
                                            <p class="jira-task-key">SCRUM-{{ $task->id }}</p>
                                            <div class="jira-task-meta">
                                                <span>{{ $priorityLabels[$task->priority] ?? ucfirst((string) $task->priority) }}</span>
                                                <span class="jira-task-avatar" title="{{ $assigneeName }}">{{ $assigneeInitial }}</span>
                                            </div>
                                            <p class="jira-task-subtasks">Sous-taches: {{ $doneSubtasks }}/{{ $totalSubtasks }}</p>
                                        </article>
                                    @empty
                                        <p class="jira-empty-col">Aucune tache</p>
                                    @endforelse
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="calendar" x-show="tab === 'calendar'" class="space-y-4" x-cloak>
            <article class="client-panel p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Calendrier des taches du projet</h3>
                    <a href="{{ route('client.tasks.calendar', ['month' => now()->format('Y-m')]) }}" class="client-button-muted">Calendrier global</a>
                </div>

                <div class="space-y-3">
                    @forelse ($tasksByDate as $date => $dateTasks)
                        <div class="rounded-xl border border-[var(--client-line)] bg-white p-3">
                            <p class="text-sm font-semibold text-slate-900">{{ \Carbon\Carbon::parse($date)->format('D d M Y') }}</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-600">
                                @foreach ($dateTasks as $task)
                                    <li>{{ $task->title }} - {{ $task->assignee?->name ?? 'Non assigne' }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-4 text-sm text-slate-500">Aucune echeance planifiee.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section id="team" x-show="tab === 'team'" class="space-y-4" x-cloak>
            <article class="client-panel p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Gestion des equipes</h3>
                    <span class="text-sm text-slate-500" data-live-members-active>{{ $stats['members_active'] }} membres actifs</span>
                </div>

                @if ($canManageProject)
                    <form method="POST" action="{{ route('client.projects.members.store', $project) }}" class="mb-5 grid gap-3 rounded-xl border border-[var(--client-line)] bg-white p-4 md:grid-cols-4">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Nouveau membre</label>
                            <select name="user_id" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm" required>
                                <option value="">Selectionner</option>
                                @foreach ($availableMembers as $candidate)
                                    <option value="{{ $candidate->id }}">{{ $candidate->name }} - {{ $candidate->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Role</label>
                            <select name="role" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm">
                                <option value="{{ \App\Models\User::ROLE_MEMBER }}">Membre</option>
                                <option value="{{ \App\Models\User::ROLE_PROJECT_MANAGER }}">Chef de projet</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="client-button w-full">Ajouter</button>
                        </div>
                    </form>
                @endif

                <div class="space-y-3">
                    @forelse ($memberWorkload as $member)
                        <div class="rounded-xl border border-[var(--client-line)] bg-white p-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $member['name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $member['email'] }}</p>
                                    <p class="text-xs text-slate-500">
                                        Role: {{ $roleLabels[$member['project_role']] ?? $member['project_role'] }} |
                                        Derniere activite: {{ $member['last_seen_at'] ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-600">
                                    <span class="rounded-full bg-slate-100 px-2 py-1">Assignees: {{ $member['tasks_assigned'] }}</span>
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-emerald-700">Acheve: {{ $member['tasks_done'] }}</span>
                                    <span class="rounded-full bg-amber-100 px-2 py-1 text-amber-700">Ouverte: {{ $member['tasks_open'] }}</span>
                                    <span class="rounded-full px-2 py-1 {{ $member['active'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $member['active'] ? 'Actif' : 'Inactif' }}
                                    </span>
                                </div>
                            </div>

                            @if ($canManageProject && $member['is_attached'])
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('client.projects.members.update', [$project, $member['id']]) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="rounded-lg border-[var(--client-line)] bg-white text-xs">
                                            <option value="{{ \App\Models\User::ROLE_MEMBER }}" @selected($member['project_role'] === \App\Models\User::ROLE_MEMBER)>Membre</option>
                                            <option value="{{ \App\Models\User::ROLE_PROJECT_MANAGER }}" @selected($member['project_role'] === \App\Models\User::ROLE_PROJECT_MANAGER)>Chef projet</option>
                                        </select>
                                        <select name="is_active" class="rounded-lg border-[var(--client-line)] bg-white text-xs">
                                            <option value="1" @selected($member['active'])>Actif</option>
                                            <option value="0" @selected(! $member['active'])>Inactif</option>
                                        </select>
                                        <button type="submit" class="client-button-muted !px-3 !py-2 !text-xs">Mettre a jour</button>
                                    </form>
                                    <form method="POST" action="{{ route('client.projects.members.remove', [$project, $member['id']]) }}" onsubmit="return confirm('Retirer ce membre du projet ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="client-button-muted !px-3 !py-2 !text-xs">Retirer</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-4 text-sm text-slate-500">Aucun membre disponible.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section id="comments" x-show="tab === 'comments'" class="space-y-4" x-cloak>
            <article class="client-panel p-5">
                <h3 class="text-lg font-semibold text-slate-900">Commentaires / Avis</h3>
                <p class="mt-1 text-xs text-slate-500">Mentions supportees dans les commentaires: utilisez <code>@prenomnom</code> ou <code>@email</code>.</p>
                @php
                    $firstTaskForComment = $tasks->first();
                @endphp
                <form
                    method="POST"
                    action="{{ $firstTaskForComment ? route('client.tasks.comments.store', $firstTaskForComment) : '#' }}"
                    id="comment-form"
                    class="mt-4 space-y-3"
                >
                    @csrf
                    <div>
                        <label for="comment-task-id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tache</label>
                        <select id="comment-task-id" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm" required @disabled(! $firstTaskForComment)>
                            <option value="">Selectionner une tache</option>
                            @foreach ($tasks as $taskOption)
                                <option value="{{ $taskOption->id }}" data-action="{{ route('client.tasks.comments.store', $taskOption) }}">
                                    {{ $taskOption->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="comment-body" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Commentaire</label>
                        <textarea id="comment-body" name="body" rows="3" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm" required @disabled(! $firstTaskForComment)></textarea>
                    </div>
                    <button type="submit" class="client-button" @disabled(! $firstTaskForComment)>Publier un avis</button>
                </form>

                <div class="mt-6 space-y-3">
                    <div id="comments-list">
                    @forelse ($recentComments as $comment)
                        <div class="rounded-xl border border-[var(--client-line)] bg-white p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-900">{{ $comment->user?->name ?? 'Systeme' }} - {{ $comment->task?->title ?? 'Tache' }}</p>
                                <span class="text-xs text-slate-500">{{ $comment->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">{{ $comment->body }}</p>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-4 text-sm text-slate-500">Aucun commentaire.</p>
                    @endforelse
                    </div>
                </div>
            </article>
        </section>

        <section id="subtasks" x-show="tab === 'subtasks'" class="space-y-4" x-cloak>
            <article class="client-panel p-5">
                <h3 class="text-lg font-semibold text-slate-900">Sous-taches</h3>
                <div class="mt-4 space-y-4">
                    @forelse ($tasks as $taskWithSubtasks)
                        <div class="rounded-xl border border-[var(--client-line)] bg-white p-4">
                            <p class="text-sm font-semibold text-slate-900">{{ $taskWithSubtasks->title }}</p>

                            @if ($taskWithSubtasks->subtasks->isNotEmpty())
                                <div class="mt-3 space-y-2">
                                    @foreach ($taskWithSubtasks->subtasks as $subtask)
                                        <form method="POST" action="{{ route('client.tasks.subtasks.update', [$taskWithSubtasks, $subtask]) }}" class="flex items-center justify-between gap-3 rounded-lg border border-[var(--client-line)] px-3 py-2">
                                            @csrf
                                            @method('PATCH')
                                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                                <input type="hidden" name="is_completed" value="0">
                                                <input type="checkbox" name="is_completed" value="1" @checked($subtask->is_completed) onchange="this.form.submit()">
                                                <span class="{{ $subtask->is_completed ? 'line-through text-slate-500' : '' }}">{{ $subtask->title }}</span>
                                            </label>
                                            <span class="text-xs text-slate-500">{{ $subtask->completed_at?->format('d/m/Y H:i') ?? '-' }}</span>
                                        </form>
                                    @endforeach
                                </div>
                            @endif

                            <form method="POST" action="{{ route('client.tasks.subtasks.store', $taskWithSubtasks) }}" class="mt-3 flex gap-2">
                                @csrf
                                <input type="text" name="title" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm" placeholder="Nouvelle sous-tache" required>
                                <button type="submit" class="client-button-muted !px-3 !py-2 !text-xs">Ajouter</button>
                            </form>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-4 text-sm text-slate-500">Aucune tache disponible.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section id="chat" x-show="tab === 'chat'" class="space-y-4" x-cloak>
            <article class="client-panel p-5">
                <h3 class="text-lg font-semibold text-slate-900">Chat interne</h3>
                <p class="mt-1 text-xs text-slate-500">Mentions supportees: utilisez <code>@prenomnom</code> ou <code>@email</code>.</p>

                <form method="POST" action="{{ route('client.projects.messages.store', $project) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="body" rows="3" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm" placeholder="Ecrire un message a l'equipe..." required></textarea>
                    <button type="submit" class="client-button">Envoyer</button>
                </form>

                <div id="messages-list" class="mt-5 space-y-3">
                    @forelse ($recentMessages as $message)
                        <div class="rounded-xl border border-[var(--client-line)] bg-white p-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-900">{{ $message->user?->name ?? 'Systeme' }}</p>
                                <span class="text-xs text-slate-500">{{ $message->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">{{ $message->body }}</p>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-4 text-sm text-slate-500">Aucun message interne.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section id="files" x-show="tab === 'files'" class="space-y-4" x-cloak>
            <article class="client-panel p-5">
                <h3 class="text-lg font-semibold text-slate-900">Partage de fichiers</h3>
                <p class="mt-1 text-xs text-slate-500">Versioning automatique actif: chaque upload du meme fichier incremente sa version.</p>

                <form method="POST" action="{{ route('client.projects.files.store', $project) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 md:grid-cols-3">
                    @csrf
                    <div class="md:col-span-2">
                        <label for="file-upload" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Fichier</label>
                        <input id="file-upload" type="file" name="file" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm" required>
                    </div>
                    <div>
                        <label for="logical_name" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Nom logique</label>
                        <input id="logical_name" name="logical_name" type="text" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm" placeholder="spec_api">
                    </div>
                    <div>
                        <label for="file_task_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">ID tache (optionnel)</label>
                        <input id="file_task_id" type="number" name="task_id" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <button type="submit" class="client-button">Uploader</button>
                    </div>
                </form>

                <div id="files-list" class="mt-5 space-y-3">
                    @forelse ($projectFiles as $file)
                        <div class="rounded-xl border border-[var(--client-line)] bg-white p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-900">{{ $file->original_name }} (v{{ $file->version }})</p>
                                <a href="{{ route('client.projects.files.download', [$project, $file]) }}" class="client-button-muted !px-3 !py-2 !text-xs">Telecharger</a>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                Par {{ $file->uploader?->name ?? 'Systeme' }} | {{ number_format(($file->size ?? 0) / 1024, 1) }} KB | {{ $file->created_at?->format('d/m/Y H:i') }}
                            </p>
                            @if ($file->task)
                                <p class="mt-1 text-xs text-slate-500">Lie a la tache: {{ $file->task->title }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-4 text-sm text-slate-500">Aucun fichier partage.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section id="report" x-show="tab === 'report'" class="space-y-4" x-cloak>
            <article class="client-panel p-5">
                <h3 class="text-lg font-semibold text-slate-900">Rapport du projet</h3>
                <p class="mt-2 text-sm text-slate-600">Analyse complete, statistiques, avancement.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('client.projects.report', $project) }}" class="client-button">Ouvrir le rapport</a>
                    <a href="{{ route('client.projects.report.download', $project) }}" class="client-button-muted">Telecharger JSON</a>
                    <a href="{{ route('client.projects.report.download-pdf', $project) }}" class="client-button-muted">Telecharger PDF</a>
                </div>
            </article>
        </section>
    </div>

    <script>
        function projectWorkspace(config) {
            return {
                tab: window.location.hash ? window.location.hash.replace('#', '') : 'overview',
                boardMode: window.localStorage.getItem('fal_board_mode') === 'scrum' ? 'scrum' : 'kanban',
                boardQuery: '',
                snapshotVersion: config.snapshotVersion,
                isDragging: false,
                draggingTaskId: null,
                pollTimer: null,
                channel: null,
                init() {
                    const tabs = ['overview', 'timeline', 'board', 'calendar', 'team', 'comments', 'subtasks', 'chat', 'files', 'report'];
                    if (!tabs.includes(this.tab)) {
                        this.tab = 'overview';
                    }

                    this.bindCommentForm();
                    this.hydrateSnapshot(config.initialSnapshot ?? null);
                    this.bindBoardDragDrop();
                    this.applyBoardModeLabels();
                    this.applyBoardFilter();
                    this.initRealtime();
                },
                setBoardMode(mode) {
                    this.boardMode = mode === 'scrum' ? 'scrum' : 'kanban';
                    window.localStorage.setItem('fal_board_mode', this.boardMode);
                    this.applyBoardModeLabels();
                },
                applyBoardModeLabels() {
                    const isScrum = this.boardMode === 'scrum';
                    const modeLabel = document.querySelector('[data-board-mode-label]');
                    if (modeLabel) {
                        modeLabel.textContent = isScrum ? 'Scrum' : 'Kanban';
                    }

                    document.querySelectorAll('[data-board-title]').forEach((node) => {
                        const label = isScrum
                            ? node.getAttribute('data-scrum-label')
                            : node.getAttribute('data-kanban-label');
                        if (label) {
                            node.textContent = label;
                        }
                    });
                },
                initRealtime() {
                    if (window.Echo) {
                        try {
                            this.channel = window.Echo.private(`project.${config.projectId}`);
                            this.channel.listen('.ProjectWorkspaceUpdated', async () => {
                                await this.fetchSnapshotAndHydrate();
                            });

                            return;
                        } catch (error) {
                            console.error('Connexion websocket indisponible', error);
                        }
                    }

                    this.startPolling();
                },
                async fetchSnapshotAndHydrate(force = false) {
                    if (this.isDragging) {
                        return;
                    }

                    try {
                        const response = await fetch(config.snapshotUrl, {
                            headers: { 'Accept': 'application/json' },
                        });
                        if (!response.ok) {
                            return;
                        }

                        const payload = await response.json();

                        if (!force && payload.version && payload.version === this.snapshotVersion) {
                            return;
                        }

                        this.hydrateSnapshot(payload);
                    } catch (error) {
                        console.error('Sync snapshot indisponible', error);
                    }
                },
                hydrateSnapshot(payload) {
                    if (!payload) {
                        return;
                    }

                    this.snapshotVersion = payload.version ?? this.snapshotVersion;
                    const boardColumns = payload.board_columns ?? {};
                    this.renderBoard(boardColumns);
                    this.renderStatusCounts(payload.status_counts ?? {}, boardColumns);
                    this.renderTimeline(payload.timeline ?? []);
                    this.renderComments(payload.recent_comments ?? []);
                    this.renderMessages(payload.recent_messages ?? []);
                    this.renderFiles(payload.files ?? []);
                    this.renderStats(payload.stats ?? {});
                },
                renderStats(stats) {
                    const membersActive = Number(stats.members_active ?? 0);

                    this.setText('[data-live-stat="progress_rate"]', `${stats.progress_rate ?? 0}%`);
                    this.setText('[data-live-stat="members_active"]', String(membersActive));
                    this.setText('[data-live-stat="tasks_overdue"]', String(stats.tasks_overdue ?? 0));
                    this.setText('[data-live-stat="average_completion_hours"]', `${stats.average_completion_hours ?? 0}h`);

                    const membersLabel = document.querySelector('[data-live-members-active]');
                    if (membersLabel) {
                        membersLabel.textContent = `${membersActive} membres actifs`;
                    }
                },
                renderStatusCounts(statusCounts, columns = {}) {
                    ['todo', 'doing', 'review', 'done'].forEach((status) => {
                        const count = Array.isArray(columns[status])
                            ? columns[status].length
                            : Number(statusCounts[status] ?? 0);
                        const badge = document.querySelector(`[data-status-count="${status}"]`);
                        if (badge) {
                            badge.textContent = String(count);
                        }
                    });

                    const backlog = Number(statusCounts.todo ?? 0);
                    const review = Number(statusCounts.review ?? 0);
                    const sprint = Math.max(Number(statusCounts.doing ?? 0) - review, 0);
                    const done = Number(statusCounts.done ?? 0);
                    this.setText('[data-scrum-metric="backlog"]', String(backlog));
                    this.setText('[data-scrum-metric="sprint"]', String(sprint));
                    this.setText('[data-scrum-metric="review"]', String(review));
                    this.setText('[data-scrum-metric="done"]', String(done));
                },
                renderBoard(columns) {
                    ['todo', 'doing', 'review', 'done'].forEach((status) => {
                        const column = document.querySelector(`[data-board-column="${status}"]`);
                        if (!column) {
                            return;
                        }

                        const tasks = Array.isArray(columns[status]) ? columns[status] : [];

                        if (tasks.length === 0) {
                            column.innerHTML = '<p class="jira-empty-col">Aucune tache</p>';

                            return;
                        }

                        column.innerHTML = tasks.map((task) => this.boardCardTemplate(task)).join('');
                    });

                    this.applyBoardModeLabels();
                    this.bindBoardDragDrop();
                    this.applyBoardFilter();
                },
                boardCardTemplate(task) {
                    const safeTaskId = Number(task.id ?? 0);
                    const title = this.escapeHtml(task.title ?? '');
                    const assignee = this.escapeHtml(task.assignee ?? 'Non assigne');
                    const assigneeInitial = this.escapeHtml((String(task.assignee ?? '?').trim().charAt(0) || '?').toUpperCase());
                    const priority = this.escapeHtml(task.priority_label ?? 'Non definie');
                    const dueDate = this.escapeHtml(task.due_date ?? 'Aucune');
                    const subtasksTotal = Number(task.subtasks_total ?? 0);
                    const subtasksDone = Number(task.subtasks_done ?? 0);

                    return `
                        <article
                            class="board-card jira-task-card cursor-move"
                            draggable="true"
                            data-task-id="${safeTaskId}"
                            data-task-title="${title}"
                        >
                            <p class="jira-task-title">${title}</p>
                            <p class="jira-task-date">${dueDate}</p>
                            <p class="jira-task-key">SCRUM-${safeTaskId}</p>
                            <div class="jira-task-meta">
                                <span>${priority}</span>
                                <span class="jira-task-avatar" title="${assignee}">${assigneeInitial}</span>
                            </div>
                            <p class="jira-task-subtasks">Sous-taches: ${subtasksDone}/${subtasksTotal}</p>
                        </article>
                    `;
                },
                applyBoardFilter() {
                    const query = this.normalizeForSearch(this.boardQuery);

                    document.querySelectorAll('.board-card').forEach((card) => {
                        const title = this.normalizeForSearch(card.getAttribute('data-task-title') ?? '');
                        const key = this.normalizeForSearch(card.querySelector('.jira-task-key')?.textContent ?? '');
                        const visible = query === '' || title.includes(query) || key.includes(query);
                        card.classList.toggle('hidden', !visible);
                    });
                },
                normalizeForSearch(value) {
                    return String(value)
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '');
                },
                renderTimeline(items) {
                    const container = document.getElementById('timeline-list');
                    if (!container) {
                        return;
                    }

                    if (!Array.isArray(items) || items.length === 0) {
                        container.innerHTML = '<p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-4 text-sm text-slate-500">Aucun evenement pour le moment.</p>';

                        return;
                    }

                    container.innerHTML = items.map((item) => {
                        const action = this.escapeHtml(item.action ?? '');
                        const date = this.escapeHtml(item.date ?? '');
                        const actor = this.escapeHtml(item.actor ?? 'Systeme');
                        const task = item.task ? `<p class="mt-1 text-xs text-slate-600">Tache: ${this.escapeHtml(item.task)}</p>` : '';
                        const meta = item.meta && Object.keys(item.meta).length > 0
                            ? `<p class="mt-1 text-xs text-slate-500">${this.escapeHtml(JSON.stringify(item.meta))}</p>`
                            : '';

                        return `
                            <div class="rounded-xl border border-[var(--client-line)] bg-white p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-900">${action}</p>
                                    <span class="text-xs text-slate-500">${date}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-600">Acteur: ${actor}</p>
                                ${task}
                                ${meta}
                            </div>
                        `;
                    }).join('');
                },
                renderComments(items) {
                    const container = document.getElementById('comments-list');
                    if (!container) {
                        return;
                    }

                    if (!Array.isArray(items) || items.length === 0) {
                        container.innerHTML = '<p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-4 text-sm text-slate-500">Aucun commentaire.</p>';

                        return;
                    }

                    container.innerHTML = items.map((item) => {
                        const author = this.escapeHtml(item.author ?? 'Systeme');
                        const task = this.escapeHtml(item.task ?? 'Tache');
                        const body = this.escapeHtml(item.body ?? '');
                        const createdAt = this.escapeHtml(item.created_at ?? '');

                        return `
                            <div class="rounded-xl border border-[var(--client-line)] bg-white p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-900">${author} - ${task}</p>
                                    <span class="text-xs text-slate-500">${createdAt}</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">${body}</p>
                            </div>
                        `;
                    }).join('');
                },
                renderMessages(items) {
                    const container = document.getElementById('messages-list');
                    if (!container) {
                        return;
                    }

                    if (!Array.isArray(items) || items.length === 0) {
                        container.innerHTML = '<p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-4 text-sm text-slate-500">Aucun message interne.</p>';

                        return;
                    }

                    container.innerHTML = items.map((item) => {
                        const author = this.escapeHtml(item.author ?? 'Systeme');
                        const body = this.escapeHtml(item.body ?? '');
                        const createdAt = this.escapeHtml(item.created_at ?? '');

                        return `
                            <div class="rounded-xl border border-[var(--client-line)] bg-white p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-900">${author}</p>
                                    <span class="text-xs text-slate-500">${createdAt}</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">${body}</p>
                            </div>
                        `;
                    }).join('');
                },
                renderFiles(items) {
                    const container = document.getElementById('files-list');
                    if (!container) {
                        return;
                    }

                    if (!Array.isArray(items) || items.length === 0) {
                        container.innerHTML = '<p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-4 text-sm text-slate-500">Aucun fichier partage.</p>';

                        return;
                    }

                    container.innerHTML = items.map((item) => {
                        const name = this.escapeHtml(item.name ?? '');
                        const version = Number(item.version ?? 1);
                        const uploader = this.escapeHtml(item.uploader ?? 'Systeme');
                        const createdAt = this.escapeHtml(item.created_at ?? '');
                        const sizeKb = (Number(item.size ?? 0) / 1024).toFixed(1);
                        const task = item.task ? `<p class="mt-1 text-xs text-slate-500">Lie a la tache: ${this.escapeHtml(item.task)}</p>` : '';

                        return `
                            <div class="rounded-xl border border-[var(--client-line)] bg-white p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-900">${name} (v${version})</p>
                                    <a href="${this.escapeHtml(item.download_url ?? '#')}" class="client-button-muted !px-3 !py-2 !text-xs">Telecharger</a>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Par ${uploader} | ${sizeKb} KB | ${createdAt}</p>
                                ${task}
                            </div>
                        `;
                    }).join('');
                },
                setText(selector, value) {
                    const target = document.querySelector(selector);
                    if (target) {
                        target.textContent = value;
                    }
                },
                escapeHtml(value) {
                    return String(value)
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#39;');
                },
                bindCommentForm() {
                    const taskSelect = document.getElementById('comment-task-id');
                    const form = document.getElementById('comment-form');
                    if (!taskSelect || !form) {
                        return;
                    }

                    taskSelect.addEventListener('change', function () {
                        const selected = taskSelect.options[taskSelect.selectedIndex];
                        const action = selected?.dataset?.action;
                        if (action) {
                            form.setAttribute('action', action);
                        }
                    });
                },
                bindBoardDragDrop() {
                    const cards = document.querySelectorAll('.board-card');
                    const columns = document.querySelectorAll('.board-column');

                    cards.forEach((card) => {
                        if (card.dataset.dragBound === '1') {
                            return;
                        }

                        card.dataset.dragBound = '1';

                        card.addEventListener('dragstart', (event) => {
                            this.isDragging = true;
                            this.draggingTaskId = Number(card.dataset.taskId);
                            event.dataTransfer.setData('text/plain', card.dataset.taskId);
                            card.classList.add('opacity-60');
                        });

                        card.addEventListener('dragend', () => {
                            this.isDragging = false;
                            this.draggingTaskId = null;
                            card.classList.remove('opacity-60');
                        });
                    });

                    columns.forEach((column) => {
                        if (column.dataset.dropBound === '1') {
                            return;
                        }

                        column.dataset.dropBound = '1';

                        column.addEventListener('dragover', (event) => {
                            event.preventDefault();
                            column.classList.add('board-column-active');
                        });

                        column.addEventListener('dragleave', () => {
                            column.classList.remove('board-column-active');
                        });

                        
                        column.addEventListener('drop', async (event) => {
                            event.preventDefault();
                            column.classList.remove('board-column-active');
                            const taskId = event.dataTransfer.getData('text/plain');
                            const status = column.dataset.boardColumn;

                            if (!taskId || !status) {
                                return;
                            }

                            // Reset dragging flag to allow snapshot fetch
                            this.isDragging = false;
                            this.draggingTaskId = null;

                            const moveUrl = `${config.moveTaskUrlPrefix}/${taskId}/move`;
                            const position = this.computeDropPosition(column, event, Number(taskId));
                            const lane = status;
                            const mappedStatus = lane === 'review'
                                ? 'doing'
                                : lane;

                            try {
                                const response = await fetch(moveUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': config.csrfToken,
                                    },
                                    body: JSON.stringify({ status: mappedStatus, lane, position }),
                                });

                                if (!response.ok) {
                                    return;
                                }

                                const payload = await response.json();
                                this.snapshotVersion = payload.snapshot_version ?? this.snapshotVersion;
                                await this.fetchSnapshotAndHydrate(true);
                            } catch (error) {
                                console.error('Impossible de deplacer la tache', error);
                            }
                        });
                    
                    });
                },
                computeDropPosition(column, event, taskId) {
                    const cards = Array.from(column.querySelectorAll('.board-card'))
                        .filter((card) => Number(card.dataset.taskId) !== taskId);

                    let position = cards.length + 1;
                    for (let index = 0; index < cards.length; index++) {
                        const card = cards[index];
                        const rect = card.getBoundingClientRect();
                        const middleY = rect.top + (rect.height / 2);

                        if (event.clientY < middleY) {
                            position = index + 1;
                            break;
                        }
                    }

                    return position;
                },
                startPolling() {
                    if (this.pollTimer) {
                        return;
                    }

                    this.pollTimer = setInterval(async () => {
                        await this.fetchSnapshotAndHydrate();
                    }, 12000);
                },
            };
        }
    </script>
</x-app-layout>
