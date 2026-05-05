<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Espace Collaborateur</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Pilotage d'Execution</h1>
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
            initialStatusBreakdown: @js($statusBreakdown ?? []),
            initialVelocity: @js($velocity ?? []),
            initialProjectLoad: @js($projectLoad ?? []),
        })"
        x-init="init()"
    >
        <style>
            .studio-dashboard-neo {
                position: relative;
                overflow: hidden;
                background:
                    radial-gradient(circle at 12% 16%, rgba(216, 96, 42, 0.14), transparent 36%),
                    radial-gradient(circle at 92% 8%, rgba(15, 118, 110, 0.16), transparent 32%),
                    rgba(255, 255, 255, 0.9);
            }
            .studio-dashboard-neo::before {
                content: '';
                position: absolute;
                inset: 0;
                pointer-events: none;
                background-image:
                    linear-gradient(to right, rgba(148, 163, 184, 0.09) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(148, 163, 184, 0.09) 1px, transparent 1px);
                background-size: 26px 26px;
                opacity: 0.28;
            }
            .studio-layer {
                position: relative;
                z-index: 1;
            }
            .neo-hero {
                border: 1px solid var(--client-line);
                border-radius: 18px;
                padding: 1rem;
                background: linear-gradient(140deg, #fff 0%, #fff6ed 55%, #ecfeff 100%);
                box-shadow: 0 18px 50px -44px rgba(15, 23, 42, 0.5);
            }
            .neo-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                border: 1px solid color-mix(in srgb, var(--client-accent) 20%, var(--client-line));
                border-radius: 999px;
                background: #fff;
                color: var(--client-accent);
                padding: 0.22rem 0.62rem;
                font-size: 0.68rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }
            .neo-chart-shell {
                border: 1px solid var(--client-line);
                border-radius: 16px;
                padding: 0.8rem;
                background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            }
            .neo-velocity-frame {
                border-radius: 14px;
                border: 1px solid #e2e8f0;
                background: #fff;
                padding: 0.5rem 0.4rem 0.3rem;
            }
            .neo-velocity-labels {
                margin-top: 0.35rem;
                display: grid;
                grid-template-columns: repeat(7, minmax(0, 1fr));
                gap: 0.25rem;
                font-size: 0.67rem;
                color: #64748b;
                text-align: center;
            }
            .neo-status-ring {
                width: 132px;
                height: 132px;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: conic-gradient(#0ea5a3 0% 100%);
                position: relative;
                box-shadow: 0 12px 28px -20px rgba(15, 23, 42, 0.5);
            }
            .neo-status-ring::before {
                content: '';
                position: absolute;
                inset: 13px;
                border-radius: 999px;
                background: #fff;
            }
            .neo-status-ring-inner {
                position: relative;
                z-index: 1;
                text-align: center;
            }
            .neo-status-ring-inner p:first-child {
                font-size: 1.75rem;
                line-height: 1;
                font-weight: 700;
                color: #0f172a;
            }
            .neo-status-ring-inner p:last-child {
                margin-top: 0.2rem;
                font-size: 0.66rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #64748b;
            }
            .neo-status-item {
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 0.55rem 0.7rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 0.78rem;
                color: #334155;
                background: #fff;
            }
            .neo-dot {
                width: 0.66rem;
                height: 0.66rem;
                border-radius: 999px;
                display: inline-block;
            }
            .neo-load-item {
                border: 1px solid var(--client-line);
                border-radius: 13px;
                background: #fff;
                padding: 0.75rem;
                transition: border-color 180ms ease;
            }
            .neo-load-item:hover {
                border-color: color-mix(in srgb, var(--client-accent) 45%, var(--client-line));
            }
            .neo-load-bar {
                margin-top: 0.45rem;
                height: 0.5rem;
                border-radius: 999px;
                background: #e2e8f0;
                overflow: hidden;
            }
            .neo-load-bar > span {
                display: block;
                height: 100%;
                border-radius: 999px;
                background: linear-gradient(90deg, #d8602a 0%, #0f766e 100%);
            }
            .neo-list-item {
                border: 1px solid var(--client-line);
                border-radius: 13px;
                background: #fff;
                padding: 0.7rem;
                transition: transform 180ms ease, border-color 180ms ease;
            }
            .neo-list-item:hover {
                transform: translateY(-1px);
                border-color: color-mix(in srgb, var(--client-accent) 42%, var(--client-line));
            }
            .neo-animate {
                animation: neo-rise 600ms ease both;
            }
            @keyframes neo-rise {
                from {
                    opacity: 0;
                    transform: translateY(12px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>

        <section class="studio-dashboard studio-dashboard-neo">
            <aside class="studio-sidebar studio-layer">
                <div>
                    <p class="studio-brand-title">FAL PMS</p>
                    <p class="studio-brand-subtitle">Delivery cockpit</p>
                </div>

                <nav class="mt-6 space-y-2 text-sm">
                    <a href="{{ route('client.dashboard') }}" class="studio-nav-item studio-nav-item-active">Dashboard</a>
                    <a href="{{ route('client.projects.index') }}" class="studio-nav-item">Projets</a>
                    <a href="{{ route('client.tasks.index') }}" class="studio-nav-item">Taches</a>
                    <a href="{{ route('client.tasks.calendar') }}" class="studio-nav-item">Calendrier</a>
                    <a href="{{ route('client.timesheets.index') }}" class="studio-nav-item">Timesheets</a>
                    <a href="{{ route('profile.edit') }}" class="studio-nav-item">Profil</a>
                </nav>

                <div class="mt-auto rounded-2xl border border-[var(--client-line)] bg-white p-3 text-xs text-slate-600">
                    <p class="font-semibold text-slate-800">Synchro live</p>
                    <p class="mt-1" data-live-updated-at>Derniere synchro: maintenant</p>
                </div>
            </aside>

            <div class="studio-main studio-layer">
                <section class="neo-hero neo-animate">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <span class="neo-chip">Control room</span>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Vue actionnable de la charge et de l'avancement</h2>
                            <p class="mt-1 text-sm text-slate-600">Suivi en direct des volumes, de la velocite et des zones a risque.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('client.projects.index') }}" class="client-button-muted !px-4 !py-2">Voir les projets</a>
                            <a href="{{ route('client.tasks.index') }}" class="client-button !px-4 !py-2">Ouvrir mes taches</a>
                        </div>
                    </div>
                </section>

                <section class="studio-kpi-grid">
                    <article class="studio-kpi-card neo-animate" style="animation-delay: 40ms;">
                        <p class="studio-kpi-label">Total projets</p>
                        <p class="studio-kpi-value" data-live-stat="projects_total">{{ $stats['projects_total'] }}</p>
                        <p class="studio-kpi-help" data-live-projects-active>{{ $stats['projects_active'] }} actifs</p>
                    </article>
                    <article class="studio-kpi-card neo-animate" style="animation-delay: 90ms;">
                        <p class="studio-kpi-label">Taches visibles</p>
                        <p class="studio-kpi-value" data-live-stat="tasks_total">{{ $stats['tasks_total'] }}</p>
                        <p class="studio-kpi-help" data-live-tasks-done>{{ $stats['tasks_done'] }} terminees</p>
                    </article>
                    <article class="studio-kpi-card neo-animate" style="animation-delay: 140ms;">
                        <p class="studio-kpi-label">Taches en retard</p>
                        <p class="studio-kpi-value" data-live-stat="tasks_overdue">{{ $stats['tasks_overdue'] }}</p>
                        <p class="studio-kpi-help">Intervention prioritaire</p>
                    </article>
                    <article class="studio-kpi-card neo-animate" style="animation-delay: 190ms;">
                        <p class="studio-kpi-label">Mes taches ouvertes</p>
                        <p class="studio-kpi-value" data-live-stat="my_open_tasks">{{ $stats['my_open_tasks'] }}</p>
                        <p class="studio-kpi-help">Focus quotidien</p>
                    </article>
                    <article class="studio-kpi-card neo-animate sm:col-span-2 xl:col-span-1" style="animation-delay: 240ms;">
                        <p class="studio-kpi-label">Notifications</p>
                        <p class="studio-kpi-value" data-live-stat="notifications_unread">{{ $stats['notifications_unread'] }}</p>
                        <p class="studio-kpi-help">Non lues</p>
                    </article>
                </section>

                <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
                    <article class="studio-panel neo-chart-shell neo-animate" style="animation-delay: 90ms;">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="studio-panel-title">Velocite des livraisons (7 jours)</h2>
                            <span class="text-xs text-slate-500">Pic: <span id="velocity-max">0</span></span>
                        </div>
                        <div class="neo-velocity-frame">
                            <svg id="velocity-chart" viewBox="0 0 420 170" class="h-[170px] w-full" role="img" aria-label="Courbe de velocite">
                                <defs>
                                    <linearGradient id="velocityAreaGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#d8602a" stop-opacity="0.35"></stop>
                                        <stop offset="100%" stop-color="#0f766e" stop-opacity="0.05"></stop>
                                    </linearGradient>
                                </defs>
                                <path id="velocity-area" fill="url(#velocityAreaGradient)"></path>
                                <path id="velocity-line" fill="none" stroke="#d8602a" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"></path>
                                <g id="velocity-dots"></g>
                            </svg>
                            <div id="velocity-labels" class="neo-velocity-labels">
                                @foreach (($velocity ?? []) as $point)
                                    <span>{{ $point['label'] ?? '-' }}</span>
                                @endforeach
                            </div>
                        </div>
                    </article>

                    <article class="studio-panel neo-chart-shell neo-animate" style="animation-delay: 130ms;">
                        <h2 class="studio-panel-title">Repartition des statuts</h2>
                        <div class="mt-4 flex flex-col items-center gap-4 xl:flex-row xl:items-start">
                            <div id="status-ring" class="neo-status-ring">
                                <div class="neo-status-ring-inner">
                                    <p id="status-completion-rate">0%</p>
                                    <p>Done rate</p>
                                </div>
                            </div>
                            <div class="w-full space-y-2">
                                <div class="neo-status-item">
                                    <span class="inline-flex items-center gap-2"><i class="neo-dot bg-sky-500"></i>To Do</span>
                                    <strong data-live-status-todo>0</strong>
                                </div>
                                <div class="neo-status-item">
                                    <span class="inline-flex items-center gap-2"><i class="neo-dot bg-amber-500"></i>Doing</span>
                                    <strong data-live-status-doing>0</strong>
                                </div>
                                <div class="neo-status-item">
                                    <span class="inline-flex items-center gap-2"><i class="neo-dot bg-emerald-500"></i>Done</span>
                                    <strong data-live-status-done>0</strong>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
                    <article class="studio-panel neo-animate" style="animation-delay: 160ms;">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="studio-panel-title">Charge par projet</h2>
                            <span class="text-xs text-slate-500">Top 5</span>
                        </div>
                        <div id="dashboard-project-load" class="space-y-3">
                            @forelse (($projectLoad ?? []) as $entry)
                                <a href="{{ $entry['url'] }}" class="neo-load-item block">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-slate-900">{{ $entry['name'] }}</p>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">{{ $entry['status'] }}</span>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between text-xs text-slate-500">
                                        <span>{{ $entry['open_tasks'] }} ouvertes</span>
                                        <span>{{ $entry['overdue_tasks'] }} en retard</span>
                                    </div>
                                    <div class="neo-load-bar">
                                        <span style="width: {{ $entry['completion'] }}%;"></span>
                                    </div>
                                </a>
                            @empty
                                <p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-3 text-sm text-slate-500">Aucune donnee de charge disponible.</p>
                            @endforelse
                        </div>
                    </article>

                    <article class="studio-panel neo-animate" style="animation-delay: 190ms;">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="studio-panel-title">Projets recents</h2>
                            <a href="{{ route('client.projects.index') }}" class="text-xs font-semibold text-[var(--client-accent)]">Voir tout</a>
                        </div>
                        <div id="dashboard-projects" class="space-y-3">
                            @forelse ($projectsPreview as $project)
                                @php
                                    $progress = $project['tasks_count'] > 0 ? (int) round(($project['tasks_done_count'] / $project['tasks_count']) * 100) : 0;
                                @endphp
                                <a href="{{ $project['url'] }}" class="neo-list-item block">
                                    <div class="flex items-center justify-between gap-2">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">{{ $project['name'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $project['owner'] }}</p>
                                        </div>
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">{{ $project['status'] }}</span>
                                    </div>
                                    <div class="mt-2 h-2 rounded-full bg-slate-100">
                                        <div class="h-2 rounded-full bg-gradient-to-r from-[var(--client-accent)] to-[var(--client-teal)]" style="width: {{ $progress }}%;"></div>
                                    </div>
                                </a>
                            @empty
                                <p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-3 text-sm text-slate-500">Aucun projet visible.</p>
                            @endforelse
                        </div>
                    </article>
                </section>

                <section class="studio-panel neo-animate" style="animation-delay: 220ms;">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="studio-panel-title">Mes taches ouvertes</h2>
                        <a href="{{ route('client.tasks.index') }}" class="text-xs font-semibold text-[var(--client-accent)]">Voir tout</a>
                    </div>
                    <div id="dashboard-tasks" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @forelse ($tasksPreview as $task)
                            <div class="neo-list-item">
                                <p class="text-sm font-semibold text-slate-900">{{ $task['title'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $task['project'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">Echeance: {{ $task['due_date'] }}</p>
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-3 text-sm text-slate-500 md:col-span-2 xl:col-span-3">Aucune tache ouverte.</p>
                        @endforelse
                    </div>
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
                        statusBreakdown: config.initialStatusBreakdown ?? {},
                        velocity: config.initialVelocity ?? [],
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
                            console.error('Websocket dashboard indisponible', error);
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
                            headers: { 'Accept': 'application/json' },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const payload = await response.json();
                        this.hydrate(payload);
                    } catch (error) {
                        console.error('Snapshot dashboard indisponible', error);
                    }
                },
                hydrate(payload) {
                    const stats = payload.stats ?? {};
                    this.setText('[data-live-stat="projects_total"]', String(stats.projects_total ?? 0));
                    this.setText('[data-live-stat="tasks_total"]', String(stats.tasks_total ?? 0));
                    this.setText('[data-live-stat="tasks_overdue"]', String(stats.tasks_overdue ?? 0));
                    this.setText('[data-live-stat="my_open_tasks"]', String(stats.my_open_tasks ?? 0));
                    this.setText('[data-live-stat="notifications_unread"]', String(stats.notifications_unread ?? 0));
                    this.setText('[data-live-projects-active]', `${stats.projects_active ?? 0} actifs`);
                    this.setText('[data-live-tasks-done]', `${stats.tasks_done ?? 0} terminees`);
                    this.setText('[data-live-updated-at]', `Derniere synchro: ${payload.updated_at ?? 'maintenant'}`);

                    this.renderVelocity(payload.velocity ?? []);
                    this.renderStatusBreakdown(payload.statusBreakdown ?? payload.status_breakdown ?? {});
                    this.renderProjectLoad(payload.projectLoad ?? payload.project_load ?? []);
                    this.renderProjects(payload.projectsPreview ?? payload.projects_preview ?? []);
                    this.renderTasks(payload.tasksPreview ?? payload.tasks_preview ?? []);
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
                    const height = 170;
                    const baselineY = 152;
                    const topPadding = 18;
                    const sidePadding = 18;
                    const values = safePoints.map((point) => Number(point.value ?? 0));
                    const maxValue = Math.max(...values, 1);
                    const step = (width - sidePadding * 2) / Math.max(values.length - 1, 1);

                    const mapped = values.map((value, index) => {
                        const x = sidePadding + (step * index);
                        const y = baselineY - ((value / maxValue) * (baselineY - topPadding));
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
                            <circle cx="${point.x.toFixed(2)}" cy="${point.y.toFixed(2)}" r="4.2" fill="#0f766e" stroke="#ffffff" stroke-width="2">
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
                    const ring = document.getElementById('status-ring');

                    this.setText('[data-live-status-todo]', String(todo));
                    this.setText('[data-live-status-doing]', String(doing));
                    this.setText('[data-live-status-done]', String(done));

                    const doneRate = total > 0 ? Math.round((done / total) * 100) : 0;
                    this.setText('#status-completion-rate', `${doneRate}%`);

                    if (!ring) {
                        return;
                    }

                    if (total === 0) {
                        ring.style.background = 'conic-gradient(#e2e8f0 0% 100%)';
                        return;
                    }

                    const todoPct = (todo / total) * 100;
                    const doingPct = (doing / total) * 100;
                    const todoStop = todoPct.toFixed(2);
                    const doingStop = (todoPct + doingPct).toFixed(2);
                    ring.style.background = `conic-gradient(
                        #0ea5e9 0% ${todoStop}%,
                        #f59e0b ${todoStop}% ${doingStop}%,
                        #10b981 ${doingStop}% 100%
                    )`;
                },
                renderProjectLoad(projectLoad) {
                    const container = document.getElementById('dashboard-project-load');
                    if (!container) {
                        return;
                    }

                    if (!Array.isArray(projectLoad) || projectLoad.length === 0) {
                        container.innerHTML = '<p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-3 text-sm text-slate-500">Aucune donnee de charge disponible.</p>';
                        return;
                    }

                    container.innerHTML = projectLoad.map((entry) => `
                        <a href="${this.escapeHtml(entry.url ?? '#')}" class="neo-load-item block">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-900">${this.escapeHtml(entry.name ?? 'Projet')}</p>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">${this.escapeHtml(entry.status ?? '-')}</span>
                            </div>
                            <div class="mt-1 flex items-center justify-between text-xs text-slate-500">
                                <span>${Number(entry.open_tasks ?? 0)} ouvertes</span>
                                <span>${Number(entry.overdue_tasks ?? 0)} en retard</span>
                            </div>
                            <div class="neo-load-bar">
                                <span style="width: ${Math.max(0, Math.min(100, Number(entry.completion ?? 0)))}%;"></span>
                            </div>
                        </a>
                    `).join('');
                },
                renderProjects(projects) {
                    const container = document.getElementById('dashboard-projects');
                    if (!container) {
                        return;
                    }

                    if (!Array.isArray(projects) || projects.length === 0) {
                        container.innerHTML = '<p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-3 text-sm text-slate-500">Aucun projet visible.</p>';
                        return;
                    }

                    container.innerHTML = projects.map((project) => {
                        const tasksCount = Number(project.tasks_count ?? 0);
                        const tasksDone = Number(project.tasks_done_count ?? 0);
                        const progress = tasksCount > 0 ? Math.round((tasksDone / tasksCount) * 100) : 0;

                        return `
                            <a href="${this.escapeHtml(project.url ?? '#')}" class="neo-list-item block">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">${this.escapeHtml(project.name ?? '')}</p>
                                        <p class="text-xs text-slate-500">${this.escapeHtml(project.owner ?? 'Non defini')}</p>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">${this.escapeHtml(project.status ?? '-')}</span>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-gradient-to-r from-[var(--client-accent)] to-[var(--client-teal)]" style="width: ${progress}%;"></div>
                                </div>
                            </a>
                        `;
                    }).join('');
                },
                renderTasks(tasks) {
                    const container = document.getElementById('dashboard-tasks');
                    if (!container) {
                        return;
                    }

                    if (!Array.isArray(tasks) || tasks.length === 0) {
                        container.innerHTML = '<p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-3 text-sm text-slate-500 md:col-span-2 xl:col-span-3">Aucune tache ouverte.</p>';
                        return;
                    }

                    container.innerHTML = tasks.map((task) => `
                        <div class="neo-list-item">
                            <p class="text-sm font-semibold text-slate-900">${this.escapeHtml(task.title ?? '')}</p>
                            <p class="mt-1 text-xs text-slate-500">${this.escapeHtml(task.project ?? 'Projet non defini')}</p>
                            <p class="mt-1 text-xs text-slate-500">Echeance: ${this.escapeHtml(task.due_date ?? 'Aucune')}</p>
                        </div>
                    `).join('');
                },
                setText(selector, value) {
                    const node = document.querySelector(selector);
                    if (node) {
                        node.textContent = value;
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
            };
        }
    </script>
</x-app-layout>
