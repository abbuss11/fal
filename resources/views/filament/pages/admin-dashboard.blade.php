<x-filament-panels::page>
    @php
        $todoCount = (int) ($statusBreakdown[\App\Models\Task::STATUS_TODO] ?? 0);
        $doingCount = (int) ($statusBreakdown[\App\Models\Task::STATUS_DOING] ?? 0);
        $doneCount = (int) ($statusBreakdown[\App\Models\Task::STATUS_DONE] ?? 0);
        $trackedTasksRaw = $todoCount + $doingCount + $doneCount;
        $trackedTasks = max($trackedTasksRaw, 1);

        $todoPct = round(($todoCount / $trackedTasks) * 100, 2);
        $doingPct = round(($doingCount / $trackedTasks) * 100, 2);
        $donePct = round(($doneCount / $trackedTasks) * 100, 2);
        $todoStop = $todoPct;
        $doingStop = $todoPct + $doingPct;

        $ringStyle = $trackedTasksRaw === 0
            ? 'background: conic-gradient(#e2e8f0 0% 100%);'
            : 'background: conic-gradient(#0ea5e9 0% '.$todoStop.'%, #f59e0b '.$todoStop.'% '.$doingStop.'%, #22c55e '.$doingStop.'% 100%);';

        $trendWidth = 560;
        $trendHeight = 210;
        $trendPadX = 24;
        $trendPadTop = 18;
        $trendBaseY = $trendHeight - 26;
        $trendCount = max(count($trend), 1);
        $trendStep = $trendCount > 1 ? ($trendWidth - ($trendPadX * 2)) / ($trendCount - 1) : 0;
        $trendMax = max(
            1,
            (int) collect($trend)->max('projects'),
            (int) collect($trend)->max('tasks_completed'),
        );

        $projectPoints = collect($trend)->values()->map(function (array $row, int $index) use ($trendPadX, $trendStep, $trendBaseY, $trendPadTop, $trendMax): array {
            $x = $trendPadX + ($index * $trendStep);
            $y = $trendBaseY - (((int) $row['projects'] / $trendMax) * ($trendBaseY - $trendPadTop));
            return ['x' => $x, 'y' => $y, 'value' => (int) $row['projects']];
        });

        $taskPoints = collect($trend)->values()->map(function (array $row, int $index) use ($trendPadX, $trendStep, $trendBaseY, $trendPadTop, $trendMax): array {
            $x = $trendPadX + ($index * $trendStep);
            $y = $trendBaseY - (((int) $row['tasks_completed'] / $trendMax) * ($trendBaseY - $trendPadTop));
            return ['x' => $x, 'y' => $y, 'value' => (int) $row['tasks_completed']];
        });

        $projectPath = $projectPoints
            ->map(fn (array $point, int $index): string => ($index === 0 ? 'M' : 'L').' '.number_format((float) $point['x'], 2, '.', '').' '.number_format((float) $point['y'], 2, '.', ''))
            ->implode(' ');
        $taskPath = $taskPoints
            ->map(fn (array $point, int $index): string => ($index === 0 ? 'M' : 'L').' '.number_format((float) $point['x'], 2, '.', '').' '.number_format((float) $point['y'], 2, '.', ''))
            ->implode(' ');

        $projectArea = $projectPoints->isNotEmpty()
            ? $projectPath.' L '.number_format((float) $projectPoints->last()['x'], 2, '.', '').' '.$trendBaseY.' L '.number_format((float) $projectPoints->first()['x'], 2, '.', '').' '.$trendBaseY.' Z'
            : '';
    @endphp

    <style>
        .fal-shell-v2 {
            display: block;
        }
        .fal-main-v2 {
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 18px;
            padding: 1rem;
            background: white;
            display: grid;
            gap: 1rem;
        }
        .fal-admin-hero {
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 14px;
            padding: 0.9rem;
            background:
                radial-gradient(circle at 8% 12%, rgba(216, 96, 42, 0.17), transparent 38%),
                radial-gradient(circle at 90% 90%, rgba(15, 118, 110, 0.14), transparent 42%),
                linear-gradient(135deg, #fff 0%, #fff8f1 52%, #ecfeff 100%);
        }
        .fal-toolbar-v2 {
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 14px;
            background: #f8fafc;
            padding: 0.75rem;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .fal-search-pill {
            border: 1px solid rgba(148, 163, 184, 0.34);
            border-radius: 999px;
            background: #fff;
            padding: 0.4rem 0.75rem;
            color: #64748b;
            font-size: 0.78rem;
            min-width: 220px;
        }
        .fal-kpi-grid-v2 {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }
        .fal-kpi-card {
            border: 1px solid rgba(148, 163, 184, 0.34);
            border-radius: 14px;
            background: #fff;
            padding: 0.85rem;
        }
        .fal-kpi-card p:first-child {
            font-size: 0.66rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 700;
            color: #64748b;
        }
        .fal-kpi-card p:nth-child(2) {
            margin-top: 0.4rem;
            font-size: 1.55rem;
            line-height: 1;
            font-weight: 700;
            color: #0f172a;
        }
        .fal-kpi-card p:last-child {
            margin-top: 0.35rem;
            font-size: 0.74rem;
            color: #64748b;
        }
        .fal-panel-v2 {
            border: 1px solid rgba(148, 163, 184, 0.34);
            border-radius: 14px;
            background: #fff;
            padding: 0.9rem;
        }
        .fal-panel-title {
            font-size: 0.95rem;
            font-weight: 650;
            color: #0f172a;
        }
        .fal-row-2 {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: 1.18fr 0.82fr;
        }
        .fal-trend-canvas {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.55rem;
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
        }
        .fal-trend-labels {
            margin-top: 0.4rem;
            display: grid;
            gap: 0.25rem;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            text-align: center;
            font-size: 0.68rem;
            color: #64748b;
        }
        .fal-status-ring {
            width: 132px;
            height: 132px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 12px 24px -18px rgba(15, 23, 42, 0.5);
        }
        .fal-status-ring::before {
            content: '';
            position: absolute;
            inset: 13px;
            border-radius: 999px;
            background: #fff;
        }
        .fal-status-ring > div {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        .fal-status-ring > div p:first-child {
            font-size: 1.65rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
        }
        .fal-status-ring > div p:last-child {
            margin-top: 0.25rem;
            font-size: 0.66rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }
        .fal-mini-list {
            margin-top: 0.75rem;
            display: grid;
            gap: 0.4rem;
        }
        .fal-mini-item {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            padding: 0.48rem 0.58rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #334155;
        }
        .fal-mini-item strong {
            color: #0f172a;
        }
        .fal-dot {
            width: 0.62rem;
            height: 0.62rem;
            border-radius: 999px;
            display: inline-block;
        }
        .fal-pipeline-item {
            margin-top: 0.55rem;
        }
        .fal-pipeline-item:first-child {
            margin-top: 0;
        }
        .fal-pipeline-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.76rem;
            color: #475569;
        }
        .fal-pipeline-bar {
            margin-top: 0.35rem;
            height: 0.45rem;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }
        .fal-pipeline-bar > span {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #d8602a 0%, #0f766e 100%);
        }
        .fal-row-3 {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: 1fr 1fr;
        }
        .fal-list-link {
            display: block;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            padding: 0.68rem;
            transition: border-color 180ms ease, transform 180ms ease;
            margin-top: 0.5rem;
        }
        .fal-list-link:first-child {
            margin-top: 0;
        }
        .fal-list-link:hover {
            border-color: rgba(216, 96, 42, 0.42);
            transform: translateY(-1px);
        }
        .fal-list-link h4 {
            font-size: 0.84rem;
            font-weight: 650;
            color: #0f172a;
        }
        .fal-list-link p {
            margin-top: 0.2rem;
            font-size: 0.73rem;
            color: #64748b;
        }
        .fal-health {
            margin-top: 0.35rem;
            height: 0.42rem;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }
        .fal-health > span {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #f59e0b 0%, #22c55e 100%);
        }
        .fal-row-4 {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: 1fr 1fr;
        }
        @media (max-width: 1350px) {
            .fal-kpi-grid-v2 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .fal-row-2,
            .fal-row-3,
            .fal-row-4 {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .fal-kpi-grid-v2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .fal-trend-labels {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
    </style>

    <div class="fal-shell-v2">
        <section class="fal-main-v2">
            <article class="fal-admin-hero">
                <p class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-600">FAL Admin</p>
                <h2 class="mt-1 text-lg font-semibold text-slate-900">Operations Hub</h2>
                <p class="mt-1 text-sm text-slate-600">Navigation unique Filament conservee. Le duplicate sidebar est supprime.</p>
            </article>

            <header class="fal-toolbar-v2">
                <span class="fal-search-pill">Recherche rapide et actions admin</span>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('create') }}" class="fi-btn fi-btn-size-sm fi-btn-color-primary">Nouveau projet</a>
                    <a href="{{ \App\Filament\Resources\TaskResource::getUrl('create') }}" class="fi-btn fi-btn-size-sm fi-btn-color-gray">Nouvelle tache</a>
                </div>
            </header>

            <section class="fal-kpi-grid-v2">
                <article class="fal-kpi-card">
                    <p>Utilisateurs</p>
                    <p>{{ $stats['users_total'] ?? 0 }}</p>
                    <p>Comptes enregistres</p>
                </article>
                <article class="fal-kpi-card">
                    <p>Projets</p>
                    <p>{{ $stats['projects_total'] ?? 0 }}</p>
                    <p>{{ $stats['projects_active'] ?? 0 }} actifs</p>
                </article>
                <article class="fal-kpi-card">
                    <p>Taches</p>
                    <p>{{ $stats['tasks_total'] ?? 0 }}</p>
                    <p>{{ $stats['tasks_done'] ?? 0 }} terminees</p>
                </article>
                <article class="fal-kpi-card">
                    <p>Completion</p>
                    <p>{{ $stats['completion_rate'] ?? 0 }}%</p>
                    <p>Done vs total</p>
                </article>
                <article class="fal-kpi-card">
                    <p>En retard</p>
                    <p>{{ $stats['tasks_overdue'] ?? 0 }}</p>
                    <p>Taches a debloquer</p>
                </article>
                <article class="fal-kpi-card">
                    <p>Projets a risque</p>
                    <p>{{ count($riskProjects) }}</p>
                    <p>Overdue detectes</p>
                </article>
            </section>

            <section class="fal-row-2">
                <article class="fal-panel-v2">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="fal-panel-title">Tendance 6 mois</h3>
                        <span class="text-xs text-slate-500">Projets crees vs taches completees</span>
                    </div>
                    <div class="fal-trend-canvas">
                        <svg viewBox="0 0 {{ $trendWidth }} {{ $trendHeight }}" class="h-[210px] w-full">
                            <defs>
                                <linearGradient id="falProjectArea" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#d8602a" stop-opacity="0.3"></stop>
                                    <stop offset="100%" stop-color="#d8602a" stop-opacity="0.04"></stop>
                                </linearGradient>
                            </defs>
                            <path d="{{ $projectArea }}" fill="url(#falProjectArea)"></path>
                            <path d="{{ $projectPath }}" fill="none" stroke="#d8602a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="{{ $taskPath }}" fill="none" stroke="#0f766e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>

                            @foreach ($projectPoints as $point)
                                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" fill="#d8602a" stroke="#fff" stroke-width="2"></circle>
                            @endforeach

                            @foreach ($taskPoints as $point)
                                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" fill="#0f766e" stroke="#fff" stroke-width="2"></circle>
                            @endforeach
                        </svg>
                    </div>
                    <div class="mt-3 flex items-center gap-4 text-xs text-slate-600">
                        <span class="inline-flex items-center gap-1.5"><i class="fal-dot" style="background:#d8602a;"></i>Projets crees</span>
                        <span class="inline-flex items-center gap-1.5"><i class="fal-dot" style="background:#0f766e;"></i>Taches completees</span>
                    </div>
                    <div class="fal-trend-labels">
                        @foreach ($trend as $entry)
                            <span>{{ $entry['label'] }}</span>
                        @endforeach
                    </div>
                </article>

                <article class="fal-panel-v2">
                    <h3 class="fal-panel-title">Distribution et pipeline</h3>
                    <div class="mt-4 flex flex-col items-center gap-4 xl:flex-row xl:items-start">
                        <div class="fal-status-ring" style="{{ $ringStyle }}">
                            <div>
                                <p>{{ (int) round(($doneCount / $trackedTasks) * 100) }}%</p>
                                <p>Done rate</p>
                            </div>
                        </div>
                        <div class="w-full fal-mini-list">
                            <div class="fal-mini-item">
                                <span class="inline-flex items-center gap-2"><i class="fal-dot bg-sky-500"></i>To Do</span>
                                <strong>{{ $todoCount }}</strong>
                            </div>
                            <div class="fal-mini-item">
                                <span class="inline-flex items-center gap-2"><i class="fal-dot bg-amber-500"></i>Doing</span>
                                <strong>{{ $doingCount }}</strong>
                            </div>
                            <div class="fal-mini-item">
                                <span class="inline-flex items-center gap-2"><i class="fal-dot bg-emerald-500"></i>Done</span>
                                <strong>{{ $doneCount }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        @foreach ($projectStatusBreakdown as $entry)
                            <div class="fal-pipeline-item">
                                <div class="fal-pipeline-meta">
                                    <span>{{ $entry['label'] }}</span>
                                    <span>{{ $entry['count'] }} ({{ $entry['percent'] }}%)</span>
                                </div>
                                <div class="fal-pipeline-bar">
                                    <span style="width: {{ $entry['percent'] }}%;"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            </section>

            <section class="fal-row-3">
                <article class="fal-panel-v2">
                    <div class="flex items-center justify-between">
                        <h3 class="fal-panel-title">Projets a risque</h3>
                        <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('index') }}" class="text-xs font-semibold text-orange-600">Voir tout</a>
                    </div>
                    @forelse ($riskProjects as $project)
                        <a href="{{ $project['edit_url'] }}" class="fal-list-link">
                            <div class="flex items-center justify-between gap-2">
                                <h4>{{ $project['name'] }}</h4>
                                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-700">{{ $project['overdue'] }} overdue</span>
                            </div>
                            <p>{{ $project['owner'] }} | {{ $project['open'] }} taches ouvertes</p>
                            <div class="fal-health">
                                <span style="width: {{ $project['health'] }}%;"></span>
                            </div>
                        </a>
                    @empty
                        <p class="mt-3 rounded-xl border border-dashed border-slate-200 p-3 text-sm text-slate-500">Aucun projet critique detecte.</p>
                    @endforelse
                </article>

                <article class="fal-panel-v2">
                    <div class="flex items-center justify-between">
                        <h3 class="fal-panel-title">Activite recente</h3>
                        <a href="{{ \App\Filament\Resources\TaskResource::getUrl('index') }}" class="text-xs font-semibold text-orange-600">Explorer</a>
                    </div>
                    @forelse ($activityFeed as $item)
                        <a href="{{ $item['task_url'] ?? \App\Filament\Resources\TaskResource::getUrl('index') }}" class="fal-list-link">
                            <div class="flex items-center justify-between gap-2">
                                <h4>{{ $item['actor'] }}</h4>
                                <span class="text-[11px] font-semibold text-slate-500">{{ $item['time'] }}</span>
                            </div>
                            <p>{{ $item['action'] }} | {{ $item['project'] }}</p>
                            <p>{{ $item['task'] }}</p>
                        </a>
                    @empty
                        <p class="mt-3 rounded-xl border border-dashed border-slate-200 p-3 text-sm text-slate-500">Aucune activite recente.</p>
                    @endforelse
                </article>
            </section>

            <section class="fal-row-4">
                <article class="fal-panel-v2">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="fal-panel-title">Projets recents</h3>
                        <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('index') }}" class="text-xs font-semibold text-orange-600">Voir tout</a>
                    </div>
                    @forelse ($projects as $project)
                        @php
                            $progress = $project['tasks_count'] > 0
                                ? (int) round(($project['tasks_done_count'] / $project['tasks_count']) * 100)
                                : 0;
                        @endphp
                        <a href="{{ $project['edit_url'] }}" class="fal-list-link">
                            <div class="flex items-center justify-between gap-2">
                                <h4>{{ $project['name'] }}</h4>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">{{ $project['status'] }}</span>
                            </div>
                            <p>{{ $project['owner'] }}</p>
                            <div class="fal-health">
                                <span style="width: {{ $progress }}%;"></span>
                            </div>
                        </a>
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-200 p-3 text-sm text-slate-500">Aucun projet recent.</p>
                    @endforelse
                </article>

                <article class="fal-panel-v2">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="fal-panel-title">Taches recentes</h3>
                        <a href="{{ \App\Filament\Resources\TaskResource::getUrl('index') }}" class="text-xs font-semibold text-orange-600">Voir tout</a>
                    </div>
                    @forelse ($tasks as $task)
                        <a href="{{ $task['edit_url'] }}" class="fal-list-link">
                            <div class="flex items-center justify-between gap-2">
                                <h4>{{ $task['title'] }}</h4>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">{{ $task['status'] }}</span>
                            </div>
                            <p>{{ $task['project'] }} | {{ $task['assignee'] }}</p>
                            <p>Priorite: {{ $task['priority'] }} | Maj: {{ $task['updated_at'] }}</p>
                        </a>
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-200 p-3 text-sm text-slate-500">Aucune tache recente.</p>
                    @endforelse
                </article>
            </section>
        </section>
    </div>
</x-filament-panels::page>
