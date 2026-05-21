<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Espace Collaborateur</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Dashboard</h1>
                <p class="mt-1 text-sm text-slate-500">Role: {{ $roleLabel }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.portal') }}" class="client-button">Panel Filament</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div
        class="client-shell"
        x-data="clientDashboardLive({
            snapshotUrl: @js(route('client.dashboard.snapshot')),
            userId: @js((int) auth()->id()),
            initialStats: @js($stats),
            initialProjects: @js($projectsPreview),
            initialTasks: @js($tasksPreview),
            initialUsers: @js($usersPreview ?? []),
            initialTeams: @js($teamsPreview ?? []),
            initialStatusBreakdown: @js($statusBreakdown ?? []),
            initialPriorityBreakdown: @js($priorityBreakdown ?? []),
            initialVelocity: @js($velocity ?? []),
            initialDueForecast: @js($dueForecast ?? []),
            initialProjectLoad: @js($projectLoad ?? []),
        })"
        x-init="init()"
    >
        <style>
            .nx-layout {
                display: grid;
                grid-template-columns: 230px 1fr;
                gap: 1rem;
                min-height: calc(100vh - 190px);
            }
            .nx-sidebar {
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                background: #ffffff;
                padding: 1rem 0.9rem;
                display: flex;
                flex-direction: column;
                box-shadow: 0 12px 32px -28px rgba(15, 23, 42, 0.45);
            }
            .nx-brand {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-weight: 700;
                color: #0f172a;
                font-size: 1.02rem;
            }
            .nx-brand-badge {
                width: 28px;
                height: 28px;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                background: linear-gradient(135deg, #0f6d93 0%, #1ba1be 100%);
                font-size: 0.78rem;
                font-weight: 700;
            }
            .nx-nav-group {
                margin-top: 1rem;
            }
            .nx-nav-label {
                padding: 0 0.35rem;
                font-size: 0.64rem;
                text-transform: uppercase;
                letter-spacing: 0.11em;
                color: #94a3b8;
                font-weight: 700;
                margin-bottom: 0.45rem;
            }
            .nx-nav-list {
                display: grid;
                gap: 0.3rem;
            }
            .nx-nav-item {
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #ffffff;
                color: #334155;
                text-decoration: none;
                font-size: 0.82rem;
                font-weight: 600;
                padding: 0.5rem 0.6rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .nx-nav-item:hover {
                border-color: #bae6fd;
                background: #f0f9ff;
            }
            .nx-nav-item.active {
                border-color: #7dd3fc;
                color: #075985;
                background: linear-gradient(135deg, #ecfeff 0%, #f0f9ff 100%);
            }
            .nx-bubble {
                min-width: 1.2rem;
                height: 1.2rem;
                border-radius: 999px;
                background: #e2e8f0;
                color: #334155;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.65rem;
                font-weight: 700;
                padding: 0 0.3rem;
            }
            .nx-side-footer {
                margin-top: auto;
                border: 1px solid #dbeafe;
                border-radius: 12px;
                background: linear-gradient(135deg, #f8fafc 0%, #ecfeff 100%);
                padding: 0.7rem;
                color: #334155;
                font-size: 0.76rem;
            }
            .nx-main {
                display: grid;
                gap: 0.95rem;
                align-content: start;
            }
            .nx-topbar {
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                background: #ffffff;
                padding: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }
            .nx-search {
                display: flex;
                align-items: center;
                gap: 0.55rem;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                border-radius: 10px;
                padding: 0.45rem 0.6rem;
                min-width: 220px;
                max-width: 420px;
                width: 100%;
            }
            .nx-search input {
                width: 100%;
                border: 0;
                background: transparent;
                outline: none;
                font-size: 0.82rem;
                color: #334155;
            }
            .nx-search-kbd {
                border: 1px solid #d1d5db;
                border-radius: 6px;
                padding: 0.04rem 0.35rem;
                font-size: 0.66rem;
                color: #64748b;
                background: #fff;
            }
            .nx-actions {
                display: flex;
                align-items: center;
                gap: 0.45rem;
                flex-wrap: wrap;
                justify-content: flex-end;
            }
            .nx-action {
                border: 1px solid #e2e8f0;
                background: #ffffff;
                border-radius: 10px;
                color: #334155;
                font-size: 0.74rem;
                font-weight: 600;
                padding: 0.38rem 0.55rem;
                white-space: nowrap;
            }
            .nx-kpi-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 0.75rem;
            }
            .nx-kpi-card {
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                background: #ffffff;
                padding: 0.75rem;
            }
            .nx-kpi-label {
                font-size: 0.69rem;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                font-weight: 700;
            }
            .nx-kpi-value {
                margin-top: 0.35rem;
                font-size: 1.65rem;
                line-height: 1.1;
                font-weight: 700;
                color: #0f172a;
            }
            .nx-kpi-help {
                margin-top: 0.32rem;
                font-size: 0.74rem;
                color: #64748b;
            }
            .nx-kpi-trend {
                margin-top: 0.4rem;
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                border: 1px solid #d1fae5;
                background: #ecfdf5;
                color: #047857;
                border-radius: 999px;
                padding: 0.1rem 0.4rem;
                font-size: 0.66rem;
                font-weight: 700;
            }
            .nx-grid-2 {
                display: grid;
                grid-template-columns: 1.6fr 1fr;
                gap: 0.75rem;
            }
            .nx-grid-3 {
                display: grid;
                grid-template-columns: 1.15fr 0.85fr;
                gap: 0.75rem;
            }
            .nx-card {
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                background: #ffffff;
                padding: 0.85rem;
                box-shadow: 0 12px 26px -30px rgba(15, 23, 42, 0.5);
            }
            .nx-card-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                margin-bottom: 0.55rem;
            }
            .nx-card-title {
                font-size: 0.86rem;
                color: #0f172a;
                font-weight: 700;
            }
            .nx-card-meta {
                font-size: 0.72rem;
                color: #64748b;
            }
            .nx-hero-metric {
                margin-bottom: 0.55rem;
            }
            .nx-hero-metric p:first-child {
                font-size: 2rem;
                line-height: 1;
                font-weight: 700;
                color: #0f172a;
            }
            .nx-hero-metric p:last-child {
                margin-top: 0.18rem;
                font-size: 0.72rem;
                color: #64748b;
            }
            .nx-chart-frame {
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                background: #f8fafc;
                padding: 0.45rem;
            }
            .nx-label-row {
                margin-top: 0.28rem;
                display: grid;
                grid-template-columns: repeat(7, minmax(0, 1fr));
                gap: 0.2rem;
                text-align: center;
                font-size: 0.67rem;
                color: #64748b;
            }
            .nx-forecast-grid {
                display: grid;
                grid-template-columns: repeat(7, minmax(0, 1fr));
                gap: 0.4rem;
            }
            .nx-forecast-item {
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #ffffff;
                padding: 0.35rem;
                text-align: center;
            }
            .nx-forecast-item p:first-child {
                font-size: 0.65rem;
                font-weight: 700;
                color: #475569;
            }
            .nx-forecast-bar-wrap {
                margin-top: 0.28rem;
                height: 60px;
                display: flex;
                align-items: flex-end;
                justify-content: center;
            }
            .nx-forecast-bar {
                width: 14px;
                border-radius: 8px;
                background: linear-gradient(180deg, #67e8f9 0%, #0ea5e9 100%);
            }
            .nx-forecast-item p:last-child {
                margin-top: 0.2rem;
                font-size: 0.7rem;
                color: #64748b;
            }
            .nx-flow {
                margin-top: 0.6rem;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 0.5rem;
                background: #f8fafc;
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 0.4rem;
                font-size: 0.72rem;
                color: #475569;
            }
            .nx-pill {
                border: 1px solid #e2e8f0;
                border-radius: 999px;
                background: #ffffff;
                padding: 0.14rem 0.45rem;
                font-weight: 700;
            }
            .nx-pill-alert {
                border-color: #fecaca;
                background: #fff1f2;
                color: #b91c1c;
            }
            .nx-priority-stack {
                height: 12px;
                border-radius: 999px;
                border: 1px solid #e2e8f0;
                overflow: hidden;
                background: #f1f5f9;
                font-size: 0;
            }
            .nx-priority-grid {
                margin-top: 0.55rem;
                display: grid;
                gap: 0.45rem;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .nx-priority-item {
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 0.45rem;
                background: #ffffff;
                font-size: 0.73rem;
                color: #475569;
            }
            .nx-priority-item strong {
                color: #0f172a;
                font-size: 0.75rem;
            }
            .nx-priority-dot {
                width: 9px;
                height: 9px;
                border-radius: 999px;
                display: inline-block;
                margin-right: 0.35rem;
                vertical-align: middle;
            }
            .nx-status-blocks {
                margin-top: 0.55rem;
                display: grid;
                gap: 0.35rem;
            }
            .nx-status-item {
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 0.45rem 0.55rem;
                background: #ffffff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 0.74rem;
                color: #334155;
            }
            .nx-list {
                display: grid;
                gap: 0.42rem;
            }
            .nx-list-item {
                border: 1px solid #e2e8f0;
                border-radius: 11px;
                background: #ffffff;
                padding: 0.55rem;
            }
            .nx-list-item-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.45rem;
            }
            .nx-list-title {
                color: #0f172a;
                font-size: 0.78rem;
                font-weight: 700;
            }
            .nx-list-sub {
                margin-top: 0.2rem;
                font-size: 0.7rem;
                color: #64748b;
            }
            .nx-progress {
                margin-top: 0.35rem;
                height: 8px;
                border-radius: 999px;
                background: #e2e8f0;
                overflow: hidden;
            }
            .nx-progress > span {
                display: block;
                height: 100%;
                border-radius: 999px;
                background: linear-gradient(90deg, #0f6d93 0%, #1ba1be 100%);
            }
            .nx-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 0.74rem;
            }
            .nx-table th,
            .nx-table td {
                padding: 0.5rem 0.35rem;
                border-bottom: 1px solid #e2e8f0;
                text-align: left;
                color: #334155;
                vertical-align: middle;
            }
            .nx-table th {
                font-size: 0.66rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #94a3b8;
                font-weight: 700;
            }
            .nx-chip {
                border: 1px solid #e2e8f0;
                border-radius: 999px;
                background: #f8fafc;
                padding: 0.13rem 0.42rem;
                font-size: 0.66rem;
                font-weight: 700;
                color: #475569;
            }
            .nx-empty {
                border: 1px dashed #cbd5e1;
                border-radius: 12px;
                background: #ffffff;
                padding: 0.8rem;
                font-size: 0.78rem;
                color: #64748b;
            }
            @media (max-width: 1280px) {
                .nx-layout {
                    grid-template-columns: 1fr;
                }
                .nx-sidebar {
                    display: none;
                }
                .nx-kpi-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
                .nx-grid-2,
                .nx-grid-3 {
                    grid-template-columns: 1fr;
                }
            }
            @media (max-width: 720px) {
                .nx-topbar {
                    flex-direction: column;
                    align-items: stretch;
                }
                .nx-actions {
                    justify-content: flex-start;
                }
                .nx-kpi-grid {
                    grid-template-columns: 1fr;
                }
                .nx-forecast-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
                .nx-priority-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <section class="nx-layout">
            <aside class="nx-sidebar">
                <div class="nx-brand">
                    <span class="nx-brand-badge">F</span>
                    FAL Nexus
                </div>

                <div class="nx-nav-group">
                    <p class="nx-nav-label">General</p>
                    <div class="nx-nav-list">
                        <a href="{{ route('client.dashboard') }}" class="nx-nav-item active">
                            Dashboard
                            <span class="nx-bubble">Live</span>
                        </a>
                        <a href="{{ route('client.projects.index') }}" class="nx-nav-item">Projects</a>
                        <a href="{{ route('client.tasks.index') }}" class="nx-nav-item">Tasks</a>
                        <a href="{{ route('client.tasks.calendar') }}" class="nx-nav-item">Calendar</a>
                    </div>
                </div>

                <div class="nx-nav-group">
                    <p class="nx-nav-label">Tools</p>
                    <div class="nx-nav-list">
                        <a href="{{ route('client.timesheets.index') }}" class="nx-nav-item">Timesheets</a>
                        @if (auth()->user()->hasPermission('teams.read'))
                            <a href="{{ route('client.teams.index') }}" class="nx-nav-item">Teams</a>
                        @endif
                        @if (auth()->user()->hasPermission('users.read'))
                            <a href="{{ route('client.users.index') }}" class="nx-nav-item">Users</a>
                        @endif
                        <a href="{{ route('profile.edit') }}" class="nx-nav-item">Profile</a>
                    </div>
                </div>

                <div class="nx-nav-group">
                    <p class="nx-nav-label">Support</p>
                    <div class="nx-nav-list">
                        <span class="nx-nav-item">
                            Notifications
                            <span class="nx-bubble" data-live-stat="notifications_unread">{{ $stats['notifications_unread'] ?? 0 }}</span>
                        </span>
                    </div>
                </div>

                <div class="nx-side-footer">
                    <p class="font-semibold text-slate-800">Realtime Sync</p>
                    <p class="mt-1" data-live-updated-at>Last sync: now</p>
                </div>
            </aside>

            <div class="nx-main">
                <header class="nx-topbar">
                    <label class="nx-search" for="nx-search-input">
                        <span class="text-slate-400">Search</span>
                        <input id="nx-search-input" type="text" placeholder="projects, tasks, members" />
                        <span class="nx-search-kbd">CTRL+K</span>
                    </label>
                    <div class="nx-actions">
                        <span class="nx-action">This month</span>
                        <span class="nx-action">Daily</span>
                        <span class="nx-action">Filter</span>
                        <span class="nx-action">Export</span>
                    </div>
                </header>

                <section class="nx-kpi-grid">
                    <article class="nx-kpi-card">
                        <p class="nx-kpi-label">Total projects</p>
                        <p class="nx-kpi-value" data-live-stat="projects_total">{{ $stats['projects_total'] ?? 0 }}</p>
                        <p class="nx-kpi-help" data-live-projects-active>{{ $stats['projects_active'] ?? 0 }} active</p>
                        <span class="nx-kpi-trend">+ {{ $stats['completion_rate'] ?? 0 }}%</span>
                    </article>
                    <article class="nx-kpi-card">
                        <p class="nx-kpi-label">Visible tasks</p>
                        <p class="nx-kpi-value" data-live-stat="tasks_total">{{ $stats['tasks_total'] ?? 0 }}</p>
                        <p class="nx-kpi-help" data-live-tasks-done>{{ $stats['tasks_done'] ?? 0 }} completed</p>
                        <span class="nx-kpi-trend">Throughput {{ $stats['throughput_7d'] ?? 0 }}</span>
                    </article>
                    <article class="nx-kpi-card">
                        <p class="nx-kpi-label">Open tasks</p>
                        <p class="nx-kpi-value" data-live-stat="tasks_open">{{ $stats['tasks_open'] ?? max(($stats['tasks_total'] ?? 0) - ($stats['tasks_done'] ?? 0), 0) }}</p>
                        <p class="nx-kpi-help">My open: <span data-live-stat="my_open_tasks">{{ $stats['my_open_tasks'] ?? 0 }}</span></p>
                        <span class="nx-kpi-trend">On time {{ $stats['on_time_rate'] ?? 0 }}%</span>
                    </article>
                    <article class="nx-kpi-card">
                        <p class="nx-kpi-label">Risk exposure</p>
                        <p class="nx-kpi-value" data-live-stat="tasks_overdue">{{ $stats['tasks_overdue'] ?? 0 }}</p>
                        <p class="nx-kpi-help">High+Urgent open: <span data-live-stat="high_priority_open">{{ $stats['high_priority_open'] ?? 0 }}</span></p>
                        <span class="nx-kpi-trend">Urgent <span data-live-stat="urgent_open">{{ $stats['urgent_open'] ?? 0 }}</span></span>
                    </article>
                </section>

                <section class="nx-grid-2">
                    <article class="nx-card">
                        <div class="nx-card-head">
                            <div>
                                <p class="nx-card-title">Delivery Overview</p>
                                <p class="nx-card-meta">7-day completion velocity</p>
                            </div>
                            <span class="nx-chip">Peak <span id="velocity-max">0</span></span>
                        </div>

                        <div class="nx-hero-metric">
                            <p data-live-stat="throughput_7d">{{ $stats['throughput_7d'] ?? 0 }}</p>
                            <p>Tasks completed on rolling 7 days</p>
                        </div>

                        <div class="nx-chart-frame">
                            <svg viewBox="0 0 420 170" class="h-[170px] w-full" role="img" aria-label="delivery velocity">
                                <defs>
                                    <linearGradient id="nxVelocityGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#0f6d93" stop-opacity="0.35"></stop>
                                        <stop offset="100%" stop-color="#1ba1be" stop-opacity="0.04"></stop>
                                    </linearGradient>
                                </defs>
                                <path id="velocity-area" fill="url(#nxVelocityGradient)"></path>
                                <path id="velocity-line" fill="none" stroke="#0f6d93" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"></path>
                                <g id="velocity-dots"></g>
                            </svg>
                            <div id="velocity-labels" class="nx-label-row">
                                @foreach (($velocity ?? []) as $point)
                                    <span>{{ $point['label'] ?? '-' }}</span>
                                @endforeach
                            </div>
                        </div>
                    </article>

                    <article class="nx-card">
                        <div class="nx-card-head">
                            <div>
                                <p class="nx-card-title">Forecast & Pipeline</p>
                                <p class="nx-card-meta">Due tasks in next 7 days</p>
                            </div>
                            <span class="nx-chip">Week load</span>
                        </div>

                        <div id="due-forecast-bars" class="nx-forecast-grid">
                            @foreach (($dueForecast ?? []) as $point)
                                <div class="nx-forecast-item">
                                    <p>{{ $point['label'] ?? '-' }}</p>
                                    <div class="nx-forecast-bar-wrap">
                                        <span class="nx-forecast-bar" style="height: {{ max(4, (int) ($point['value'] ?? 0) * 8) }}px;"></span>
                                    </div>
                                    <p>{{ $point['value'] ?? 0 }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="nx-flow">
                            <span class="nx-pill">Todo <strong data-live-pipeline="todo">0</strong></span>
                            <span>&gt;</span>
                            <span class="nx-pill">Doing <strong data-live-pipeline="doing">0</strong></span>
                            <span>&gt;</span>
                            <span class="nx-pill">Done <strong data-live-pipeline="done">0</strong></span>
                            <span class="nx-pill nx-pill-alert">Overdue <strong data-live-pipeline="overdue">0</strong></span>
                        </div>
                    </article>
                </section>

                <section class="nx-grid-3">
                    <article class="nx-card">
                        <div class="nx-card-head">
                            <div>
                                <p class="nx-card-title">Task Distribution</p>
                                <p class="nx-card-meta">Status + priority diagram</p>
                            </div>
                            <span class="nx-chip" id="status-completion-rate">0%</span>
                        </div>

                        <div class="nx-status-blocks">
                            <div class="nx-status-item">
                                <span>To Do</span>
                                <strong data-live-status-todo>0</strong>
                            </div>
                            <div class="nx-status-item">
                                <span>Doing</span>
                                <strong data-live-status-doing>0</strong>
                            </div>
                            <div class="nx-status-item">
                                <span>Done</span>
                                <strong data-live-status-done>0</strong>
                            </div>
                        </div>

                        <div class="mt-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.09em] text-slate-500">Priority stack</p>
                            <div id="priority-stack-bar" class="nx-priority-stack"></div>
                            <div id="priority-legend-grid" class="nx-priority-grid"></div>
                        </div>
                    </article>

                    <article class="nx-card">
                        <div class="nx-card-head">
                            <div>
                                <p class="nx-card-title">Project Load</p>
                                <p class="nx-card-meta">Open, overdue, completion</p>
                            </div>
                            <a href="{{ route('client.projects.index') }}" class="nx-chip">See all</a>
                        </div>

                        <div id="dashboard-project-load" class="nx-list">
                            @forelse (($projectLoad ?? []) as $entry)
                                <a href="{{ $entry['url'] }}" class="nx-list-item block">
                                    <div class="nx-list-item-head">
                                        <p class="nx-list-title">{{ $entry['name'] }}</p>
                                        <span class="nx-chip">{{ $entry['status'] }}</span>
                                    </div>
                                    <p class="nx-list-sub">{{ $entry['open_tasks'] }} open | {{ $entry['overdue_tasks'] }} overdue</p>
                                    <div class="nx-progress">
                                        <span style="width: {{ $entry['completion'] }}%;"></span>
                                    </div>
                                </a>
                            @empty
                                <p class="nx-empty">No project load data.</p>
                            @endforelse
                        </div>
                    </article>
                </section>

                <section class="nx-grid-2">
                    <article class="nx-card">
                        <div class="nx-card-head">
                            <div>
                                <p class="nx-card-title">Portfolio Table</p>
                                <p class="nx-card-meta">Projects and progress</p>
                            </div>
                            <a href="{{ route('client.projects.index') }}" class="nx-chip">See all</a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="nx-table">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Status</th>
                                        <th>Tasks</th>
                                        <th>Done</th>
                                        <th>Rate</th>
                                    </tr>
                                </thead>
                                <tbody id="dashboard-projects-table">
                                    @forelse ($projectsPreview as $project)
                                        @php
                                            $tasksCount = (int) ($project['tasks_count'] ?? 0);
                                            $tasksDone = (int) ($project['tasks_done_count'] ?? 0);
                                            $rate = $tasksCount > 0 ? (int) round(($tasksDone / $tasksCount) * 100) : 0;
                                        @endphp
                                        <tr>
                                            <td>
                                                <a href="{{ $project['url'] }}" class="font-semibold text-slate-900 hover:text-cyan-700">{{ $project['name'] }}</a>
                                                <p class="text-[11px] text-slate-500">{{ $project['owner'] }}</p>
                                            </td>
                                            <td><span class="nx-chip">{{ $project['status'] }}</span></td>
                                            <td>{{ $tasksCount }}</td>
                                            <td>{{ $tasksDone }}</td>
                                            <td>{{ $rate }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-slate-500">No projects available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="nx-card">
                        <div class="nx-card-head">
                            <div>
                                <p class="nx-card-title">Execution Feed</p>
                                <p class="nx-card-meta">My tasks, teams, resources</p>
                            </div>
                            <span class="nx-chip" data-live-stat="timesheet_hours_week">{{ $stats['timesheet_hours_week'] ?? 0 }}</span>
                        </div>

                        <div id="dashboard-tasks" class="nx-list">
                            @forelse ($tasksPreview as $task)
                                <div class="nx-list-item">
                                    <p class="nx-list-title">{{ $task['title'] }}</p>
                                    <p class="nx-list-sub">{{ $task['project'] }}</p>
                                    <p class="nx-list-sub">Due: {{ $task['due_date'] }}</p>
                                </div>
                            @empty
                                <p class="nx-empty">No open tasks.</p>
                            @endforelse
                        </div>

                        @if (auth()->user()->hasPermission('teams.read'))
                            <div class="mt-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.09em] text-slate-500">Teams</p>
                                <div id="dashboard-teams" class="nx-list mt-2">
                                    @forelse (($teamsPreview ?? []) as $team)
                                        <a href="{{ $team['url'] }}" class="nx-list-item block">
                                            <div class="nx-list-item-head">
                                                <p class="nx-list-title">{{ $team['name'] }}</p>
                                                <span class="nx-chip">{{ $team['status'] }}</span>
                                            </div>
                                            <p class="nx-list-sub">Owner: {{ $team['owner'] }} | {{ $team['active_members'] }} active</p>
                                        </a>
                                    @empty
                                        <p class="nx-empty">No team available.</p>
                                    @endforelse
                                </div>
                            </div>
                        @endif

                        @if (auth()->user()->hasPermission('users.read'))
                            <div class="mt-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.09em] text-slate-500">Resources</p>
                                <div id="dashboard-users" class="nx-list mt-2">
                                    @forelse (($usersPreview ?? []) as $member)
                                        <div class="nx-list-item">
                                            <p class="nx-list-title">{{ $member['name'] }}</p>
                                            <p class="nx-list-sub">{{ $member['role'] }} | {{ $member['last_seen_at'] }}</p>
                                            <p class="nx-list-sub">{{ $member['open_tasks'] }} open tasks | {{ $member['active_teams'] }} teams</p>
                                        </div>
                                    @empty
                                        <p class="nx-empty">No active user.</p>
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    </article>
                </section>
            </div>
        </section>
    </div>

    <script>
        function clientDashboardLive(config) {
            return {
                pollTimer: null,
                channel: null,
                init() {
                    this.hydrate({
                        stats: config.initialStats ?? {},
                        projectsPreview: config.initialProjects ?? [],
                        tasksPreview: config.initialTasks ?? [],
                        usersPreview: config.initialUsers ?? [],
                        teamsPreview: config.initialTeams ?? [],
                        statusBreakdown: config.initialStatusBreakdown ?? {},
                        priorityBreakdown: config.initialPriorityBreakdown ?? {},
                        velocity: config.initialVelocity ?? [],
                        dueForecast: config.initialDueForecast ?? [],
                        projectLoad: config.initialProjectLoad ?? [],
                    });
                    this.initRealtime();
                },
                initRealtime() {
                    if (window.Echo) {
                        try {
                            this.channel = window.Echo.private(`dashboard.${config.userId}`);
                            this.channel.listen('.UserDashboardUpdated', async () => {
                                await this.fetchSnapshot();
                            });

                            return;
                        } catch (error) {
                            console.error('Websocket dashboard unavailable', error);
                        }
                    }

                    this.startPolling();
                },
                startPolling() {
                    if (this.pollTimer) {
                        return;
                    }

                    this.pollTimer = setInterval(async () => {
                        await this.fetchSnapshot();
                    }, 14000);
                },
                async fetchSnapshot() {
                    try {
                        const response = await fetch(config.snapshotUrl, {
                            headers: { Accept: 'application/json' },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const payload = await response.json();
                        this.hydrate(payload);
                    } catch (error) {
                        console.error('Dashboard snapshot unavailable', error);
                    }
                },
                hydrate(payload) {
                    const stats = payload.stats ?? {};
                    const statusBreakdown = payload.statusBreakdown ?? payload.status_breakdown ?? {};

                    this.setText('[data-live-stat="projects_total"]', String(stats.projects_total ?? 0));
                    this.setText('[data-live-stat="tasks_total"]', String(stats.tasks_total ?? 0));
                    this.setText('[data-live-stat="tasks_overdue"]', String(stats.tasks_overdue ?? 0));
                    this.setText('[data-live-stat="tasks_due_week"]', String(stats.tasks_due_week ?? 0));
                    this.setText('[data-live-stat="my_open_tasks"]', String(stats.my_open_tasks ?? 0));
                    this.setText('[data-live-stat="teams_total"]', String(stats.teams_total ?? 0));
                    this.setText('[data-live-stat="users_total"]', String(stats.users_total ?? 0));
                    this.setText('[data-live-stat="timesheet_hours_week"]', String(stats.timesheet_hours_week ?? 0));
                    this.setText('[data-live-stat="notifications_unread"]', String(stats.notifications_unread ?? 0));
                    this.setText('[data-live-stat="tasks_open"]', String(stats.tasks_open ?? 0));
                    this.setText('[data-live-stat="completion_rate"]', `${stats.completion_rate ?? 0}%`);
                    this.setText('[data-live-stat="on_time_rate"]', `${stats.on_time_rate ?? 0}%`);
                    this.setText('[data-live-stat="throughput_7d"]', String(stats.throughput_7d ?? 0));
                    this.setText('[data-live-stat="high_priority_open"]', String(stats.high_priority_open ?? 0));
                    this.setText('[data-live-stat="urgent_open"]', String(stats.urgent_open ?? 0));
                    this.setText('[data-live-projects-active]', `${stats.projects_active ?? 0} active`);
                    this.setText('[data-live-tasks-done]', `${stats.tasks_done ?? 0} completed`);
                    this.setText('[data-live-updated-at]', `Last sync: ${payload.updated_at ?? 'now'}`);

                    this.renderVelocity(payload.velocity ?? []);
                    this.renderStatusBreakdown(statusBreakdown);
                    this.renderPriorityBreakdown(payload.priorityBreakdown ?? payload.priority_breakdown ?? {}, stats);
                    this.renderDueForecast(payload.dueForecast ?? payload.due_forecast ?? []);
                    this.renderPipelineDiagram(statusBreakdown, stats);
                    this.renderProjectLoad(payload.projectLoad ?? payload.project_load ?? []);
                    this.renderProjects(payload.projectsPreview ?? payload.projects_preview ?? []);
                    this.renderTasks(payload.tasksPreview ?? payload.tasks_preview ?? []);
                    this.renderTeams(payload.teamsPreview ?? payload.teams_preview ?? []);
                    this.renderUsers(payload.usersPreview ?? payload.users_preview ?? []);
                },
                renderVelocity(points) {
                    const area = document.getElementById('velocity-area');
                    const line = document.getElementById('velocity-line');
                    const dots = document.getElementById('velocity-dots');
                    const labels = document.getElementById('velocity-labels');
                    const maxHolder = document.getElementById('velocity-max');

                    if (!area || !line || !dots || !labels) {
                        return;
                    }

                    const safePoints = Array.isArray(points) ? points : [];
                    if (safePoints.length === 0) {
                        area.setAttribute('d', '');
                        line.setAttribute('d', '');
                        dots.innerHTML = '';
                        labels.innerHTML = '';
                        if (maxHolder) {
                            maxHolder.textContent = '0';
                        }
                        return;
                    }

                    const width = 420;
                    const baselineY = 152;
                    const topPadding = 18;
                    const sidePadding = 18;
                    const values = safePoints.map((point) => Number(point.value ?? 0));
                    const maxValue = Math.max(...values, 1);
                    const step = (width - sidePadding * 2) / Math.max(values.length - 1, 1);

                    const mapped = values.map((value, index) => {
                        const x = sidePadding + step * index;
                        const y = baselineY - (value / maxValue) * (baselineY - topPadding);
                        return { x, y, value };
                    });

                    const linePath = mapped
                        .map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x.toFixed(2)} ${point.y.toFixed(2)}`)
                        .join(' ');
                    const areaPath = `${linePath} L ${mapped[mapped.length - 1].x.toFixed(2)} ${baselineY} L ${mapped[0].x.toFixed(2)} ${baselineY} Z`;

                    area.setAttribute('d', areaPath);
                    line.setAttribute('d', linePath);

                    dots.innerHTML = mapped.map((point) => {
                        return `
                            <circle cx="${point.x.toFixed(2)}" cy="${point.y.toFixed(2)}" r="4.2" fill="#1ba1be" stroke="#ffffff" stroke-width="2">
                                <title>${point.value}</title>
                            </circle>
                        `;
                    }).join('');

                    labels.innerHTML = safePoints
                        .map((point) => `<span>${this.escapeHtml(point.label ?? '-')}</span>`)
                        .join('');

                    if (maxHolder) {
                        maxHolder.textContent = String(maxValue);
                    }
                },
                renderStatusBreakdown(breakdown) {
                    const todo = Number(breakdown.todo ?? 0);
                    const doing = Number(breakdown.doing ?? 0);
                    const done = Number(breakdown.done ?? 0);
                    const total = Math.max(todo + doing + done, 0);

                    this.setText('[data-live-status-todo]', String(todo));
                    this.setText('[data-live-status-doing]', String(doing));
                    this.setText('[data-live-status-done]', String(done));

                    const doneRate = total > 0 ? Math.round((done / total) * 100) : 0;
                    this.setText('#status-completion-rate', `${doneRate}%`);
                },
                renderPriorityBreakdown(breakdown, stats) {
                    const stack = document.getElementById('priority-stack-bar');
                    const legend = document.getElementById('priority-legend-grid');
                    if (!stack || !legend) {
                        return;
                    }

                    const order = [
                        { key: 'low', label: 'Low', color: '#10b981' },
                        { key: 'medium', label: 'Medium', color: '#0ea5e9' },
                        { key: 'high', label: 'High', color: '#f59e0b' },
                        { key: 'urgent', label: 'Urgent', color: '#ef4444' },
                    ];

                    const total = Math.max(Number(stats.tasks_total ?? 0), 0);
                    const entries = order.map((item) => {
                        const value = Number(breakdown[item.key] ?? 0);
                        const percent = total > 0 ? (value / total) * 100 : 0;

                        return {
                            ...item,
                            value,
                            percent,
                        };
                    });

                    if (total === 0) {
                        stack.innerHTML = '<span class="block h-full w-full bg-slate-200"></span>';
                    } else {
                        stack.innerHTML = entries
                            .filter((entry) => entry.value > 0)
                            .map((entry) => {
                                const width = Math.max(entry.percent, 3);
                                return `<span class="inline-block h-full" style="width:${width}%; background:${entry.color};"></span>`;
                            })
                            .join('');
                    }

                    legend.innerHTML = entries.map((entry) => {
                        const percent = total > 0 ? Math.round(entry.percent) : 0;
                        return `
                            <div class="nx-priority-item">
                                <p><span class="nx-priority-dot" style="background:${entry.color};"></span><strong>${entry.label}</strong></p>
                                <p class="mt-1 text-xs text-slate-500">${entry.value} tasks (${percent}%)</p>
                            </div>
                        `;
                    }).join('');
                },
                renderDueForecast(points) {
                    const container = document.getElementById('due-forecast-bars');
                    if (!container) {
                        return;
                    }

                    const safePoints = Array.isArray(points) ? points : [];
                    if (safePoints.length === 0) {
                        container.innerHTML = '<p class="nx-empty col-span-full">No forecast data.</p>';
                        return;
                    }

                    const maxValue = Math.max(...safePoints.map((point) => Number(point.value ?? 0)), 1);

                    container.innerHTML = safePoints.map((point) => {
                        const value = Number(point.value ?? 0);
                        const label = this.escapeHtml(point.label ?? '-');
                        const height = value > 0 ? Math.max(Math.round((value / maxValue) * 60), 8) : 4;

                        return `
                            <div class="nx-forecast-item">
                                <p>${label}</p>
                                <div class="nx-forecast-bar-wrap">
                                    <span class="nx-forecast-bar" style="height:${height}px;"></span>
                                </div>
                                <p>${value}</p>
                            </div>
                        `;
                    }).join('');
                },
                renderPipelineDiagram(breakdown, stats) {
                    const todo = Number(breakdown.todo ?? 0);
                    const doing = Number(breakdown.doing ?? 0);
                    const done = Number(breakdown.done ?? 0);
                    const overdue = Number(stats.tasks_overdue ?? 0);

                    this.setText('[data-live-pipeline="todo"]', String(todo));
                    this.setText('[data-live-pipeline="doing"]', String(doing));
                    this.setText('[data-live-pipeline="done"]', String(done));
                    this.setText('[data-live-pipeline="overdue"]', String(overdue));
                },
                renderProjectLoad(projectLoad) {
                    const container = document.getElementById('dashboard-project-load');
                    if (!container) {
                        return;
                    }

                    if (!Array.isArray(projectLoad) || projectLoad.length === 0) {
                        container.innerHTML = '<p class="nx-empty">No project load data.</p>';
                        return;
                    }

                    container.innerHTML = projectLoad.map((entry) => `
                        <a href="${this.escapeHtml(entry.url ?? '#')}" class="nx-list-item block">
                            <div class="nx-list-item-head">
                                <p class="nx-list-title">${this.escapeHtml(entry.name ?? 'Project')}</p>
                                <span class="nx-chip">${this.escapeHtml(entry.status ?? '-')}</span>
                            </div>
                            <p class="nx-list-sub">${Number(entry.open_tasks ?? 0)} open | ${Number(entry.overdue_tasks ?? 0)} overdue</p>
                            <div class="nx-progress">
                                <span style="width:${Math.max(0, Math.min(100, Number(entry.completion ?? 0)))}%;"></span>
                            </div>
                        </a>
                    `).join('');
                },
                renderProjects(projects) {
                    const container = document.getElementById('dashboard-projects-table');
                    if (!container) {
                        return;
                    }

                    if (!Array.isArray(projects) || projects.length === 0) {
                        container.innerHTML = '<tr><td colspan="5" class="text-slate-500">No projects available.</td></tr>';
                        return;
                    }

                    container.innerHTML = projects.map((project) => {
                        const tasksCount = Number(project.tasks_count ?? 0);
                        const tasksDone = Number(project.tasks_done_count ?? 0);
                        const rate = tasksCount > 0 ? Math.round((tasksDone / tasksCount) * 100) : 0;

                        return `
                            <tr>
                                <td>
                                    <a href="${this.escapeHtml(project.url ?? '#')}" class="font-semibold text-slate-900 hover:text-cyan-700">${this.escapeHtml(project.name ?? '')}</a>
                                    <p class="text-[11px] text-slate-500">${this.escapeHtml(project.owner ?? 'N/A')}</p>
                                </td>
                                <td><span class="nx-chip">${this.escapeHtml(project.status ?? '-')}</span></td>
                                <td>${tasksCount}</td>
                                <td>${tasksDone}</td>
                                <td>${rate}%</td>
                            </tr>
                        `;
                    }).join('');
                },
                renderTasks(tasks) {
                    const container = document.getElementById('dashboard-tasks');
                    if (!container) {
                        return;
                    }

                    if (!Array.isArray(tasks) || tasks.length === 0) {
                        container.innerHTML = '<p class="nx-empty">No open tasks.</p>';
                        return;
                    }

                    container.innerHTML = tasks.map((task) => `
                        <div class="nx-list-item">
                            <p class="nx-list-title">${this.escapeHtml(task.title ?? '')}</p>
                            <p class="nx-list-sub">${this.escapeHtml(task.project ?? 'Project')}</p>
                            <p class="nx-list-sub">Due: ${this.escapeHtml(task.due_date ?? 'None')}</p>
                        </div>
                    `).join('');
                },
                renderTeams(teams) {
                    const container = document.getElementById('dashboard-teams');
                    if (!container) {
                        return;
                    }

                    if (!Array.isArray(teams) || teams.length === 0) {
                        container.innerHTML = '<p class="nx-empty">No team available.</p>';
                        return;
                    }

                    container.innerHTML = teams.map((team) => `
                        <a href="${this.escapeHtml(team.url ?? '#')}" class="nx-list-item block">
                            <div class="nx-list-item-head">
                                <p class="nx-list-title">${this.escapeHtml(team.name ?? 'Team')}</p>
                                <span class="nx-chip">${this.escapeHtml(team.status ?? '-')}</span>
                            </div>
                            <p class="nx-list-sub">Owner: ${this.escapeHtml(team.owner ?? 'N/A')} | ${Number(team.active_members ?? 0)} active</p>
                        </a>
                    `).join('');
                },
                renderUsers(users) {
                    const container = document.getElementById('dashboard-users');
                    if (!container) {
                        return;
                    }

                    if (!Array.isArray(users) || users.length === 0) {
                        container.innerHTML = '<p class="nx-empty">No active user.</p>';
                        return;
                    }

                    container.innerHTML = users.map((member) => `
                        <div class="nx-list-item">
                            <p class="nx-list-title">${this.escapeHtml(member.name ?? '')}</p>
                            <p class="nx-list-sub">${this.escapeHtml(member.role ?? 'N/A')} | ${this.escapeHtml(member.last_seen_at ?? 'N/A')}</p>
                            <p class="nx-list-sub">${Number(member.open_tasks ?? 0)} open tasks | ${Number(member.active_teams ?? 0)} teams</p>
                        </div>
                    `).join('');
                },
                setText(selector, value) {
                    const nodes = document.querySelectorAll(selector);
                    if (nodes.length === 0) {
                        return;
                    }

                    nodes.forEach((node) => {
                        node.textContent = value;
                    });
                },
                escapeHtml(value) {
                    return String(value)
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#39;');
                },
            };
        }
    </script>
</x-app-layout>
