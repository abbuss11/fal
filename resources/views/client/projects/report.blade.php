@php
    $stats = $report['stats'];
    $statusBreakdown = $report['status_breakdown'];
    $priorityBreakdown = $report['priority_breakdown'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="fal-brand-kicker">Project Intelligence</p>
                <h1 class="mt-1 text-2xl font-semibold client-heading-accent">{{ $report['project']['name'] }} - Rapport SaaS</h1>
                <p class="mt-1 text-sm text-slate-500">Genere le {{ $report['generated_at'] }} • Data live synchronisee.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('client.projects.show', $project) }}" class="client-button-muted">Retour workspace</a>
                <a href="{{ route('client.projects.report.download', $project) }}" class="client-button">Exporter JSON</a>
                <a href="{{ route('client.projects.report.download-pdf', $project) }}" class="client-button-muted">Exporter PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="client-shell space-y-6">
        <section class="saas-hero">
            <div class="saas-hero-content grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Progression</p>
                    <p class="saas-kpi-value" data-live-report-stat="progress_rate">{{ $stats['progress_rate'] }}%</p>
                    <p class="saas-kpi-help">Livraison globale</p>
                </article>
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Taches totales</p>
                    <p class="saas-kpi-value" data-live-report-stat="tasks_total">{{ $stats['tasks_total'] }}</p>
                    <p class="saas-kpi-help">{{ $stats['tasks_done'] ?? 0 }} cloturees</p>
                </article>
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Membres actifs</p>
                    <p class="saas-kpi-value" data-live-report-stat="members_active">{{ $stats['members_active'] }}</p>
                    <p class="saas-kpi-help">Collaboration projet</p>
                </article>
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Retards</p>
                    <p class="saas-kpi-value" data-live-report-stat="tasks_overdue">{{ $stats['tasks_overdue'] }}</p>
                    <p class="saas-kpi-help">Signal de risque</p>
                </article>
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
            <article class="saas-panel">
                <h2 class="saas-panel-title">Analyse des statuts</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($statusLabels as $key => $label)
                        @php
                            $statusCount = (int) ($statusBreakdown[$key] ?? 0);
                            $statusPercent = $stats['tasks_total'] > 0 ? (int) round(($statusCount / $stats['tasks_total']) * 100) : 0;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm text-slate-700">
                                <span>{{ $label }}</span>
                                <span class="font-semibold">{{ $statusCount }} ({{ $statusPercent }}%)</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-200">
                                <div class="h-1.5 rounded-full bg-gradient-to-r from-[var(--client-accent)] to-[var(--client-accent-soft)]" style="width: {{ $statusPercent }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="saas-panel">
                <h2 class="saas-panel-title">Analyse des priorites</h2>
                <div class="mt-4 space-y-2">
                    @foreach ($priorityLabels as $key => $label)
                        <div class="saas-list-item flex items-center justify-between">
                            <span>{{ $label }}</span>
                            <span class="font-semibold text-slate-800">{{ $priorityBreakdown[$key] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="saas-panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="saas-panel-title">Membres et charge de travail</h2>
                <span class="text-xs text-slate-500">{{ count($report['member_workload']) }} membres</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--client-line)]">
                    <thead class="saas-table-head">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Membre</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Role</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actif</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Assignees</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Open</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Done</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--client-line)] bg-white">
                        @foreach ($report['member_workload'] as $member)
                            <tr>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ $member['name'] }}</td>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ $roleLabels[$member['project_role']] ?? $member['project_role'] }}</td>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ $member['active'] ? 'Oui' : 'Non' }}</td>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ $member['tasks_assigned'] }}</td>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ $member['tasks_open'] }}</td>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ $member['tasks_done'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="saas-panel">
            <h2 class="saas-panel-title">Timeline recente</h2>
            <div class="mt-4 space-y-2" id="report-timeline-list">
                @forelse ($report['timeline'] as $event)
                    <div class="saas-list-item">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900">{{ $event['action'] }}</p>
                            <span class="text-xs text-slate-500">{{ $event['date'] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Acteur: {{ $event['actor'] }} | Tache: {{ $event['task'] ?? 'N/A' }}</p>
                    </div>
                @empty
                    <p class="saas-empty">Aucun evenement.</p>
                @endforelse
            </div>
        </section>
    </div>

    <script>
        (function () {
            const projectId = @js((int) $project->id);
            const snapshotUrl = @js(route('client.projects.snapshot', $project));

            async function refreshReport() {
                try {
                    const response = await fetch(snapshotUrl, {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    const stats = payload.stats ?? {};
                    const timeline = payload.timeline ?? [];

                    setText('[data-live-report-stat="progress_rate"]', `${stats.progress_rate ?? 0}%`);
                    setText('[data-live-report-stat="tasks_total"]', String(stats.tasks_total ?? 0));
                    setText('[data-live-report-stat="members_active"]', String(stats.members_active ?? 0));
                    setText('[data-live-report-stat="tasks_overdue"]', String(stats.tasks_overdue ?? 0));

                    renderTimeline(timeline);
                } catch (error) {
                    console.error('Sync rapport indisponible', error);
                }
            }

            function renderTimeline(events) {
                const list = document.getElementById('report-timeline-list');
                if (!list) {
                    return;
                }

                if (!Array.isArray(events) || events.length === 0) {
                    list.innerHTML = '<p class="saas-empty">Aucun evenement.</p>';

                    return;
                }

                list.innerHTML = events.map((event) => `
                    <div class="saas-list-item">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900">${escapeHtml(event.action ?? '')}</p>
                            <span class="text-xs text-slate-500">${escapeHtml(event.date ?? '')}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Acteur: ${escapeHtml(event.actor ?? 'Systeme')} | Tache: ${escapeHtml(event.task ?? 'N/A')}</p>
                    </div>
                `).join('');
            }

            function setText(selector, value) {
                const node = document.querySelector(selector);
                if (node) {
                    node.textContent = value;
                }
            }

            function escapeHtml(value) {
                return String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            if (window.Echo) {
                try {
                    window.Echo.private(`project.${projectId}`)
                        .listen('.ProjectWorkspaceUpdated', async () => {
                            await refreshReport();
                        });

                    return;
                } catch (error) {
                    console.error('Rapport indisponible', error);
                }
            }

            setInterval(refreshReport, 15000);
        })();
    </script>
</x-app-layout>
