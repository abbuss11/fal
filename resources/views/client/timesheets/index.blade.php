<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Gestion du temps</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Timesheet</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('client.tasks.calendar') }}" class="client-button-muted">Calendrier</a>
                <a href="{{ route('client.dashboard') }}" class="client-button-muted">Dashboard</a>
            </div>
        </div>
    </x-slot>

    <div class="client-shell space-y-5">
        @if (session('status'))
            <div class="client-panel border-l-4 border-l-emerald-500 p-4 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <section class="client-panel p-4">
            <form method="GET" action="{{ route('client.timesheets.index') }}" class="grid gap-3 md:grid-cols-3">
                <div>
                    <label for="month" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Mois</label>
                    <input id="month" name="month" type="month" value="{{ request('month', $currentMonth->format('Y-m')) }}" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm">
                </div>
                <div>
                    <label for="scope" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Portee</label>
                    <select id="scope" name="scope" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm">
                        <option value="">Mes entrees</option>
                        <option value="team" @selected(request('scope') === 'team')>Equipe</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="client-button">Appliquer</button>
                    <a href="{{ route('client.timesheets.index') }}" class="client-button-muted">Reset</a>
                </div>
            </form>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <article class="client-stat">
                <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Heures du mois</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ number_format($totalHours, 2) }}</p>
            </article>

            @foreach ($hoursByProject->take(2) as $item)
                <article class="client-stat">
                    <p class="text-xs uppercase tracking-[0.12em] text-slate-500">{{ $item->project?->name ?? 'Projet' }}</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ number_format((float) $item->total_hours, 2) }}</p>
                </article>
            @endforeach
        </section>

        <section class="client-panel p-5">
            <h2 class="text-lg font-semibold text-slate-900">Nouvelle entree</h2>
            <form method="POST" action="{{ route('client.timesheets.store') }}" class="mt-4 grid gap-3 md:grid-cols-2">
                @csrf
                <div>
                    <label for="project_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Projet</label>
                    <select id="project_id" name="project_id" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm" required>
                        <option value="">Selectionner</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="task_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tache (optionnel)</label>
                    <input id="task_id" name="task_id" type="number" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm" placeholder="ID tache">
                </div>
                <div>
                    <label for="work_date" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                    <input id="work_date" name="work_date" type="date" value="{{ now()->toDateString() }}" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm" required>
                </div>
                <div>
                    <label for="hours" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Heures</label>
                    <input id="hours" name="hours" type="number" step="0.25" min="0.25" max="24" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm" required>
                </div>
                <div class="md:col-span-2">
                    <label for="note" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Note</label>
                    <textarea id="note" name="note" rows="3" class="w-full rounded-xl border-[var(--client-line)] bg-white text-sm"></textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="client-button">Enregistrer</button>
                </div>
            </form>
        </section>

        <section class="client-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--client-line)]">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Utilisateur</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Projet</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tache</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Heures</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--client-line)] bg-white">
                        @forelse ($entries as $entry)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->work_date?->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->user?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->project?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->task?->title ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ number_format((float) $entry->hours, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->note ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">Aucune entree.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div>
            {{ $entries->links() }}
        </div>
    </div>
</x-app-layout>

