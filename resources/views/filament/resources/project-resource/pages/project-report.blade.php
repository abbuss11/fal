<x-filament-panels::page>
    @php
        $stats = $report['stats'];
        $statusBreakdown = $report['status_breakdown'];
        $priorityBreakdown = $report['priority_breakdown'];
        $timeline = $report['timeline'] ?? [];
        $memberWorkload = $report['member_workload'] ?? [];
        $totalTasks = (int) ($stats['tasks_total'] ?? 0);
        $completedTasks = (int) ($stats['tasks_done'] ?? 0);
        $completionRate = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;
    @endphp

    <div class="space-y-5">
        <section class="rounded-2xl border border-gray-200 bg-gradient-to-br from-white via-orange-50/35 to-cyan-50/55 p-4 dark:border-white/10 dark:from-gray-900 dark:via-gray-900 dark:to-gray-950">
            <p class="text-[11px] font-semibold uppercase tracking-[0.13em] text-gray-500 dark:text-gray-400">Filament project report</p>
            <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ $report['project']['name'] }}</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Rapport operationnel consolide et pilotage en temps reel.</p>
            <div class="mt-3">
                <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.11em] text-gray-500 dark:text-gray-400">
                    <span>Completion globale</span>
                    <span>{{ $completionRate }}%</span>
                </div>
                <div class="mt-1.5 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-1.5 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500" style="width: {{ $completionRate }}%;"></div>
                </div>
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <p class="text-xs uppercase tracking-wide text-gray-500">Progression</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['progress_rate'] }}%</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $completedTasks }} / {{ $totalTasks }} taches</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <p class="text-xs uppercase tracking-wide text-gray-500">Membres actifs</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['members_active'] }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ count($memberWorkload) }} membres analyses</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <p class="text-xs uppercase tracking-wide text-gray-500">Retards</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['tasks_overdue'] }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Risque operationnel</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <p class="text-xs uppercase tracking-wide text-gray-500">Cycle moyen</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['average_completion_hours'] ?? 0 }}h</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Temps de completion</p>
            </article>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Analyse des statuts</h3>
                <div class="mt-3 space-y-3">
                    @foreach ($statusBreakdown as $status => $count)
                        @php
                            $percent = $totalTasks > 0 ? (int) round(($count / $totalTasks) * 100) : 0;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm text-gray-700 dark:text-gray-200">
                                <span>{{ \App\Models\Task::statusOptions()[$status] ?? strtoupper((string) $status) }}</span>
                                <span class="font-semibold">{{ $count }} ({{ $percent }}%)</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-1.5 rounded-full bg-gradient-to-r from-primary-500 to-cyan-500" style="width: {{ $percent }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Analyse des priorites</h3>
                <div class="mt-3 space-y-2">
                    @foreach ($priorityBreakdown as $priority => $count)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10">
                            <span class="text-gray-700 dark:text-gray-200">{{ \App\Models\Task::priorityOptions()[$priority] ?? strtoupper((string) $priority) }}</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Membres et charge</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Membre</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Actif</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Assignees</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Ouvertes</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Done</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-gray-900">
                        @foreach ($memberWorkload as $member)
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $member['name'] }}</td>
                                <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ \App\Models\User::roleOptions()[$member['project_role']] ?? $member['project_role'] }}</td>
                                <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $member['active'] ? 'Oui' : 'Non' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $member['tasks_assigned'] }}</td>
                                <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $member['tasks_open'] }}</td>
                                <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $member['tasks_done'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Timeline recente</h3>
            <div class="mt-3 space-y-2">
                @forelse ($timeline as $event)
                    <article class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-gray-900">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $event['action'] }}</p>
                            <span class="text-xs text-gray-500">{{ $event['date'] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $event['actor'] }}
                            @if($event['task'])
                                | {{ $event['task'] }}
                            @endif
                        </p>
                    </article>
                @empty
                    <p class="rounded-lg border border-dashed border-gray-300 bg-white px-3 py-3 text-sm text-gray-500 dark:border-white/15 dark:bg-gray-900 dark:text-gray-400">
                        Aucun evenement disponible.
                    </p>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
