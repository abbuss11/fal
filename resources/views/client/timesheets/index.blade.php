<x-app-layout>
    @php
        $entriesCollection = $entries->getCollection();
        $trackedDays = max((int) $entriesCollection->pluck('work_date')->filter()->unique()->count(), 1);
        $averagePerDay = $totalHours > 0 ? round($totalHours / $trackedDays, 2) : 0;
        $topProject = $hoursByProject->sortByDesc('total_hours')->first();
        $topProjectName = $topProject?->project?->name ?? 'N/A';
        $topProjectHours = $topProject ? (float) $topProject->total_hours : 0;
        $currentUser = auth()->user();
        $canManageAnyTimesheet = $currentUser->isAdmin() || $currentUser->hasPermission('timesheets.update');
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="fal-brand-kicker">Gestion du temps</p>
                <h1 class="mt-1 text-2xl font-semibold client-heading-accent">Timesheet Ops Center</h1>
                <p class="mt-1 text-sm text-slate-500">Suis la capacite, la charge et les entrees horaires de ton equipe.</p>
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

        <section class="saas-hero">
            <div class="saas-hero-content grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Heures du mois</p>
                    <p class="saas-kpi-value">{{ number_format($totalHours, 2) }}</p>
                    <p class="saas-kpi-help">{{ $currentMonth->translatedFormat('F Y') }}</p>
                </article>
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Moyenne / jour actif</p>
                    <p class="saas-kpi-value">{{ number_format($averagePerDay, 2) }}</p>
                    <p class="saas-kpi-help">{{ $trackedDays }} jours traces</p>
                </article>
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Projet dominant</p>
                    <p class="saas-kpi-value text-xl">{{ $topProjectName }}</p>
                    <p class="saas-kpi-help">{{ number_format($topProjectHours, 2) }} h</p>
                </article>
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Scope</p>
                    <p class="saas-kpi-value">{{ $teamMode ? 'Equipe' : 'Personnel' }}</p>
                    <p class="saas-kpi-help">{{ $entries->total() }} entrees visibles</p>
                </article>
            </div>
        </section>

        <section class="client-panel p-4">
            <form method="GET" action="{{ route('client.timesheets.index') }}" class="saas-command-bar">
                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label for="month" class="saas-label">Mois</label>
                        <input id="month" name="month" type="month" value="{{ request('month', $currentMonth->format('Y-m')) }}" class="saas-field">
                    </div>
                    <div>
                        <label for="scope" class="saas-label">Portee</label>
                        <select id="scope" name="scope" class="saas-field">
                            <option value="">Mes entrees</option>
                            <option value="team" @selected(request('scope') === 'team')>Equipe</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="client-button">Appliquer</button>
                        <a href="{{ route('client.timesheets.index') }}" class="client-button-muted">Reset</a>
                    </div>
                </div>
            </form>
        </section>

        <section class="grid gap-5 xl:grid-cols-[0.95fr_1.05fr]">
            <article class="client-panel p-5">
                <h2 class="saas-panel-title">Nouvelle entree</h2>
                <p class="mt-1 text-sm text-slate-500">Ajoute rapidement le temps passe sur un projet ou une tache.</p>

                <form method="POST" action="{{ route('client.timesheets.store') }}" class="mt-4 grid gap-3 md:grid-cols-2">
                    @csrf
                    <div class="md:col-span-2">
                        <label for="project_id" class="saas-label">Projet</label>
                        <select id="project_id" name="project_id" class="saas-field" required>
                            <option value="">Selectionner</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="task_id" class="saas-label">ID tache (optionnel)</label>
                        <input id="task_id" name="task_id" type="number" class="saas-field" placeholder="Ex: 182">
                    </div>
                    <div>
                        <label for="hours" class="saas-label">Heures</label>
                        <input id="hours" name="hours" type="number" step="0.25" min="0.25" max="24" class="saas-field" required>
                    </div>
                    <div class="md:col-span-2">
                        <label for="work_date" class="saas-label">Date</label>
                        <input id="work_date" name="work_date" type="date" value="{{ now()->toDateString() }}" class="saas-field" required>
                    </div>
                    <div class="md:col-span-2">
                        <label for="note" class="saas-label">Note</label>
                        <textarea id="note" name="note" rows="3" class="saas-field"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="client-button">Enregistrer</button>
                    </div>
                </form>
            </article>

            <article class="client-panel p-5">
                <h2 class="saas-panel-title">Repartition par projet</h2>
                <p class="mt-1 text-sm text-slate-500">Top de contribution sur la periode selectionnee.</p>

                <div class="mt-4 space-y-3">
                    @forelse ($hoursByProject as $item)
                        @php
                            $projectHours = (float) $item->total_hours;
                            $projectRate = $totalHours > 0 ? (int) round(($projectHours / $totalHours) * 100) : 0;
                        @endphp
                        <article class="saas-list-item">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-slate-900">{{ $item->project?->name ?? 'Projet N/A' }}</p>
                                <p class="text-sm font-semibold text-slate-700">{{ number_format($projectHours, 2) }} h</p>
                            </div>
                            <div class="mt-2 h-1.5 rounded-full bg-slate-200">
                                <div class="h-1.5 rounded-full bg-gradient-to-r from-[var(--client-accent)] to-[var(--client-accent-soft)]" style="width: {{ $projectRate }}%;"></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $projectRate }}% de la charge mensuelle</p>
                        </article>
                    @empty
                        <p class="saas-empty">Aucune donnee de charge pour cette periode.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="saas-table">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--client-line)]">
                    <thead class="saas-table-head">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Utilisateur</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Projet</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tache</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Heures</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Note</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--client-line)] bg-white">
                        @forelse ($entries as $entry)
                            @php
                                $canEditEntry = $canManageAnyTimesheet || $entry->user_id === $currentUser->id;
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->work_date?->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->user?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->project?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->task?->title ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ number_format((float) $entry->hours, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->note ?: '-' }}</td>
                                <td class="px-4 py-3 text-right align-top">
                                    @if ($canEditEntry)
                                        <details class="inline-block text-left">
                                            <summary class="cursor-pointer list-none text-sm font-semibold text-[var(--client-accent)] hover:text-cyan-700">
                                                Modifier
                                            </summary>
                                            <div class="mt-2 w-[300px] rounded-xl border border-[var(--client-line)] bg-white p-3 shadow-[0_16px_36px_-30px_rgba(15,23,42,0.45)]">
                                                <form method="POST" action="{{ route('client.timesheets.update', $entry) }}" class="space-y-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="date" name="work_date" value="{{ optional($entry->work_date)->toDateString() }}" class="saas-field" required>
                                                    <input type="number" name="hours" step="0.25" min="0.25" max="24" value="{{ $entry->hours }}" class="saas-field" required>
                                                    <input type="text" name="note" value="{{ $entry->note }}" class="saas-field" placeholder="Note (optionnelle)">
                                                    <button type="submit" class="client-button !w-full !px-3 !py-2">Sauver</button>
                                                </form>
                                                <form method="POST" action="{{ route('client.timesheets.destroy', $entry) }}" class="mt-2" onsubmit="return confirm('Supprimer cette entree timesheet?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="client-button-muted !w-full !px-3 !py-2 !text-rose-700">Supprimer</button>
                                                </form>
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">Aucune entree.</td>
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
