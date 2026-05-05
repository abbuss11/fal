<x-filament-panels::page>
    @php
        $stats = $report['stats'];
    @endphp

    <div class="space-y-5">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <p class="text-xs uppercase tracking-wide text-gray-500">Progression</p>
                <p class="mt-2 text-2xl font-semibold">{{ $stats['progress_rate'] }}%</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <p class="text-xs uppercase tracking-wide text-gray-500">Taches</p>
                <p class="mt-2 text-2xl font-semibold">{{ $stats['tasks_total'] }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <p class="text-xs uppercase tracking-wide text-gray-500">Membres actifs</p>
                <p class="mt-2 text-2xl font-semibold">{{ $stats['members_active'] }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <p class="text-xs uppercase tracking-wide text-gray-500">Retards</p>
                <p class="mt-2 text-2xl font-semibold">{{ $stats['tasks_overdue'] }}</p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-semibold">Analyse des statuts</h3>
                <div class="mt-3 space-y-2">
                    @foreach ($report['status_breakdown'] as $status => $count)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10">
                            <span>{{ \App\Models\Task::statusOptions()[$status] ?? $status }}</span>
                            <span class="font-semibold">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-semibold">Analyse des priorites</h3>
                <div class="mt-3 space-y-2">
                    @foreach ($report['priority_breakdown'] as $priority => $count)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10">
                            <span>{{ \App\Models\Task::priorityOptions()[$priority] ?? $priority }}</span>
                            <span class="font-semibold">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-sm font-semibold">Membres et charge</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Membre</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Actif</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Assignees</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Done</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($report['member_workload'] as $member)
                            <tr>
                                <td class="px-3 py-2 text-sm">{{ $member['name'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ \App\Models\User::roleOptions()[$member['project_role']] ?? $member['project_role'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $member['active'] ? 'Oui' : 'Non' }}</td>
                                <td class="px-3 py-2 text-sm">{{ $member['tasks_assigned'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $member['tasks_done'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-sm font-semibold">Timeline</h3>
            <div class="mt-3 space-y-2">
                @foreach ($report['timeline'] as $event)
                    <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10">
                        <p class="font-semibold">{{ $event['action'] }}</p>
                        <p class="text-xs text-gray-500">{{ $event['date'] }} - {{ $event['actor'] }} @if($event['task']) - {{ $event['task'] }} @endif</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-panels::page>
