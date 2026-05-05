<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Espace Collaborateur</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Projets</h1>
            </div>
            <a href="{{ route('client.dashboard') }}" class="client-button-muted">Retour dashboard</a>
        </div>
    </x-slot>

    <div class="client-shell">
        <div class="client-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--client-line)]">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Projet</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Chef de projet</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Progression</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--client-line)] bg-white">
                        @forelse ($projects as $project)
                            @php
                                $progress = $project->tasks_count > 0
                                    ? (int) round(($project->tasks_done_count / $project->tasks_count) * 100)
                                    : 0;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $project->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $project->owner?->name ?? 'Non defini' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ strtoupper((string) $project->status) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $project->tasks_done_count }} / {{ $project->tasks_count }} ({{ $progress }}%)</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <a href="{{ route('client.projects.show', $project) }}" class="text-sm font-semibold text-[var(--client-accent)] hover:text-orange-700">
                                            Workspace
                                        </a>
                                        <a href="{{ route('client.projects.report', $project) }}" class="text-sm font-semibold text-teal-700 hover:text-teal-600">
                                            Rapport
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Aucun projet disponible.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $projects->links() }}
        </div>
    </div>
</x-app-layout>
