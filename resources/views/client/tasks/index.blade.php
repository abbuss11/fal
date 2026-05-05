<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Espace Collaborateur</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Taches</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('client.tasks.calendar') }}" class="client-button-muted">Calendrier</a>
                <a href="{{ route('client.dashboard') }}" class="client-button-muted">Retour dashboard</a>
            </div>
        </div>
    </x-slot>

    <div class="client-shell space-y-5">
        <section class="client-panel p-4">
            <form method="GET" action="{{ route('client.tasks.index') }}" class="grid gap-3 md:grid-cols-3">
                <div>
                    <label for="status" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Statut</label>
                    <select id="status" name="status" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm">
                        <option value="">Tous</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="project_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Projet</label>
                    <select id="project_id" name="project_id" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm">
                        <option value="">Tous</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected($selectedProjectId === $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="client-button">Filtrer</button>
                    <a href="{{ route('client.tasks.index') }}" class="client-button-muted">Reset</a>
                </div>
            </form>
        </section>

        <section class="client-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--client-line)]">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tache</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Projet</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Assigne</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Sous-taches</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Echeance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--client-line)] bg-white">
                        @forelse ($tasks as $task)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $task->title }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $task->project?->name ?? 'Non defini' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $task->assignee?->name ?? 'Non assigne' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $statuses[$task->status] ?? strtoupper((string) $task->status) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $task->subtasks_done_count ?? 0 }}/{{ $task->subtasks_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $task->due_date?->format('d/m/Y H:i') ?? 'Aucune' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Aucune tache trouvee.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div>
            {{ $tasks->links() }}
        </div>
    </div>
</x-app-layout>
