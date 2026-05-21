<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="fal-brand-kicker">Espace Collaborateur</p>
                <h1 class="mt-1 text-2xl font-semibold client-heading-accent">Teams Delivery Hub</h1>
                <p class="mt-1 text-sm text-slate-500">Gestion CRUD des equipes avec recap charge et couverture projets.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($canCreateTeam)
                    <a href="{{ route('client.teams.create') }}" class="client-button">Nouvelle equipe</a>
                @endif
                <a href="{{ route('client.dashboard') }}" class="client-button-muted">Retour dashboard</a>
            </div>
        </div>
    </x-slot>

    <div class="client-shell space-y-5">
        <section class="saas-hero">
            <div class="saas-hero-content">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <span class="client-badge">Team performance</span>
                        <h2 class="mt-3 text-2xl font-semibold text-slate-900">Vue transversale des equipes de delivery</h2>
                        <p class="mt-1 text-sm text-slate-600">Suivi de la capacite, de la charge active et de la composition des squads.</p>
                    </div>
                    <div class="saas-kpi-grid w-full max-w-4xl sm:grid-cols-3 xl:grid-cols-6">
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Total equipes</p>
                            <p class="saas-kpi-value">{{ $stats['total'] }}</p>
                            <p class="saas-kpi-help">Visibles par le compte</p>
                        </article>
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Actives</p>
                            <p class="saas-kpi-value">{{ $stats['active'] }}</p>
                            <p class="saas-kpi-help">{{ $stats['inactive'] }} inactives</p>
                        </article>
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Mes equipes</p>
                            <p class="saas-kpi-value">{{ $stats['owned'] }}</p>
                            <p class="saas-kpi-help">Owner actuel</p>
                        </article>
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Membres actifs</p>
                            <p class="saas-kpi-value">{{ $stats['members'] }}</p>
                            <p class="saas-kpi-help">Ressources disponibles</p>
                        </article>
                        <article class="saas-kpi-card sm:col-span-2 xl:col-span-2">
                            <p class="saas-kpi-label">Taches ouvertes (equipes)</p>
                            <p class="saas-kpi-value">{{ $stats['open_tasks'] }}</p>
                            <p class="saas-kpi-help">Backlog + Doing actifs</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="client-panel p-4">
            <form method="GET" action="{{ route('client.teams.index') }}" class="saas-command-bar">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1.2fr_0.7fr_auto]">
                    <label class="block">
                        <span class="saas-label">Recherche</span>
                        <input type="text" name="q" value="{{ request('q') }}" class="saas-field" placeholder="Equipe, owner, description">
                    </label>
                    <label class="block">
                        <span class="saas-label">Etat</span>
                        <select name="active" class="saas-field">
                            <option value="">Tous</option>
                            <option value="1" @selected(request('active') === '1')>Active</option>
                            <option value="0" @selected(request('active') === '0')>Inactive</option>
                        </select>
                    </label>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="client-button">Filtrer</button>
                        <a href="{{ route('client.teams.index') }}" class="client-button-muted">Reset</a>
                    </div>
                </div>
            </form>
        </section>

        <section class="saas-table">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--client-line)]">
                    <thead class="saas-table-head">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Equipe</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Owner</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Membres</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Maj</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--client-line)] bg-white">
                        @forelse ($teams as $team)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $team->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $team->description ? \Illuminate\Support\Str::limit($team->description, 90) : 'Sans description' }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $team->owner?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <p>{{ (int) $team->active_members_count }} actifs / {{ (int) $team->members_count }}</p>
                                    <p class="text-xs text-slate-500">{{ (int) $team->managers_count }} profils manager</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $team->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $team->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $team->updated_at?->diffForHumans() ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('client.teams.show', $team) }}" class="client-button-muted !px-3 !py-2 !text-xs">Ouvrir</a>
                                        @if ($canUpdateTeam)
                                            <a href="{{ route('client.teams.edit', $team) }}" class="client-button-muted !px-3 !py-2 !text-xs">Modifier</a>
                                        @endif
                                        @if ($canDeleteTeam)
                                            <form method="POST" action="{{ route('client.teams.destroy', $team) }}" onsubmit="return confirm('Supprimer cette equipe ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="client-button-muted !px-3 !py-2 !text-xs !border-rose-200 !text-rose-700">Supprimer</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Aucune equipe disponible.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div>
            {{ $teams->links() }}
        </div>
    </div>
</x-app-layout>
