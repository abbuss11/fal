@php
    $stats = $report['stats'];
    $statusBreakdown = $report['status_breakdown'];
    $priorityBreakdown = $report['priority_breakdown'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Rapport Projet</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $report['project']['name'] }}</h1>
                <p class="mt-1 text-sm text-slate-500">Genere le {{ $report['generated_at'] }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('client.projects.show', $project) }}" class="client-button-muted">Retour projet</a>
                <a href="{{ route('client.projects.report.download', $project) }}" class="client-button">Telecharger JSON</a>
                <a href="{{ route('client.projects.report.download-pdf', $project) }}" class="client-button-muted">Telecharger PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="client-shell space-y-6">
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <article class="client-stat">
                <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Progression</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900" data-live-report-stat="progress_rate">{{ $stats['progress_rate'] }}%</p>
            </article>
            <article class="client-stat">
                <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Taches totales</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900" data-live-report-stat="tasks_total">{{ $stats['tasks_total'] }}</p>
            </article>
            <article class="client-stat">
                <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Membres actifs</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900" data-live-report-stat="members_active">{{ $stats['members_active'] }}</p>
            </article>
            <article class="client-stat">
                <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Retards</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900" data-live-report-stat="tasks_overdue">{{ $stats['tasks_overdue'] }}</p>
            </article>
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
            <article class="client-panel p-5">
                <h2 class="text-lg font-semibold text-slate-900">Analyse des statuts</h2>
                <div class="mt-4 space-y-2">
                    @foreach ($statusLabels as $key => $label)
                        <div class="flex items-center justify-between rounded-xl border border-[var(--client-line)] bg-white px-3 py-2 text-sm">
                            <span>{{ $label }}</span>
                            <span class="font-semibold">{{ $statusBreakdown[$key] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="client-panel p-5">
                <h2 class="text-lg font-semibold text-slate-900">Analyse des priorites</h2>
                <div class="mt-4 space-y-2">
                    @foreach ($priorityLabels as $key => $label)
                        <div class="flex items-center justify-between rounded-xl border border-[var(--client-line)] bg-white px-3 py-2 text-sm">
                            <span>{{ $label }}</span>
                            <span class="font-semibold">{{ $priorityBreakdown[$key] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="client-panel p-5">
            <h2 class="text-lg font-semibold text-slate-900">Membres et charge de travail</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--client-line)]">
                    <thead class="bg-slate-50">
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

        <section class="client-panel p-5">
            <h2 class="text-lg font-semibold text-slate-900">Timeline recente</h2>
            <div class="mt-4 space-y-2" id="report-timeline-list">
                @forelse ($report['timeline'] as $event)
                    <div class="rounded-xl border border-[var(--client-line)] bg-white p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900">{{ $event['action'] }}</p>
                            <span class="text-xs text-slate-500">{{ $event['date'] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Acteur: {{ $event['actor'] }} | Tache: {{ $event['task'] ?? 'N/A' }}</p>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-3 text-sm text-slate-500">Aucun evenement.</p>
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
                    list.innerHTML = '<p class="rounded-xl border border-dashed border-[var(--client-line)] bg-white p-3 text-sm text-slate-500">Aucun evenement.</p>';

                    return;
                }

                list.innerHTML = events.map((event) => `
                    <div class="rounded-xl border border-[var(--client-line)] bg-white p-3">
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
