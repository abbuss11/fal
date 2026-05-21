<x-app-layout>
    @php
        $pageUsers = $users->getCollection();
        $openTasksPage = (int) $pageUsers->sum('open_tasks_count');
        $activeTeamsPage = (int) $pageUsers->sum('active_teams_count');
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="fal-brand-kicker">Espace Collaborateur</p>
                <h1 class="mt-1 text-2xl font-semibold client-heading-accent">Users Management Hub</h1>
                <p class="mt-1 text-sm text-slate-500">CRUD utilisateurs avec vision capacite et activite equipe.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($canCreateUser)
                    <a href="{{ route('client.users.create') }}" class="client-button">Nouveau user</a>
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
                        <span class="client-badge">People operations</span>
                        <h2 class="mt-3 text-2xl font-semibold text-slate-900">Pilotage des comptes, roles et disponibilites</h2>
                        <p class="mt-1 text-sm text-slate-600">Vue recapitulative de la structure humaine et de la charge ouverte.</p>
                    </div>
                    <div class="saas-kpi-grid w-full max-w-4xl sm:grid-cols-3 xl:grid-cols-6">
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Total</p>
                            <p class="saas-kpi-value">{{ $stats['total'] }}</p>
                            <p class="saas-kpi-help">Utilisateurs visibles</p>
                        </article>
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Actifs</p>
                            <p class="saas-kpi-value">{{ $stats['active'] }}</p>
                            <p class="saas-kpi-help">{{ $stats['inactive'] }} inactifs</p>
                        </article>
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Managers</p>
                            <p class="saas-kpi-value">{{ $stats['managers'] }}</p>
                            <p class="saas-kpi-help">Leadership delivery</p>
                        </article>
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Members</p>
                            <p class="saas-kpi-value">{{ $stats['members'] }}</p>
                            <p class="saas-kpi-help">Execution team</p>
                        </article>
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Clients</p>
                            <p class="saas-kpi-value">{{ $stats['clients'] }}</p>
                            <p class="saas-kpi-help">Comptes externes</p>
                        </article>
                        <article class="saas-kpi-card">
                            <p class="saas-kpi-label">Charge page</p>
                            <p class="saas-kpi-value">{{ $openTasksPage }}</p>
                            <p class="saas-kpi-help">{{ $activeTeamsPage }} equipes actives</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="client-panel p-4">
            <form method="GET" action="{{ route('client.users.index') }}" class="saas-command-bar">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1.2fr_0.7fr_0.7fr_auto]">
                    <label class="block">
                        <span class="saas-label">Recherche</span>
                        <input type="text" name="q" value="{{ request('q') }}" class="saas-field" placeholder="Nom, email, poste">
                    </label>
                    <label class="block">
                        <span class="saas-label">Role</span>
                        <select name="role" class="saas-field">
                            <option value="">Tous</option>
                            @foreach ($roleOptions as $roleValue => $roleLabel)
                                <option value="{{ $roleValue }}" @selected(request('role') === $roleValue)>{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="saas-label">Etat</span>
                        <select name="active" class="saas-field">
                            <option value="">Tous</option>
                            <option value="1" @selected(request('active') === '1')>Actif</option>
                            <option value="0" @selected(request('active') === '0')>Inactif</option>
                        </select>
                    </label>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="client-button">Filtrer</button>
                        <a href="{{ route('client.users.index') }}" class="client-button-muted">Reset</a>
                    </div>
                </div>
            </form>
        </section>

        <section class="saas-table">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--client-line)]">
                    <thead class="saas-table-head">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Utilisateur</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Charge</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Derniere activite</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--client-line)] bg-white">
                        @forelse ($users as $managedUser)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $managedUser->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $managedUser->email }}</p>
                                    @if ($managedUser->job_title)
                                        <p class="mt-1 text-xs text-slate-500">{{ $managedUser->job_title }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $roleOptions[$managedUser->role] ?? strtoupper($managedUser->role) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $managedUser->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $managedUser->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <p>{{ (int) $managedUser->open_tasks_count }} taches ouvertes</p>
                                    <p class="text-xs text-slate-500">{{ (int) $managedUser->owned_projects_count }} projets owner | {{ (int) $managedUser->active_teams_count }} equipes</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $managedUser->last_seen_at?->diffForHumans() ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        @if ($canUpdateUser)
                                            <a href="{{ route('client.users.edit', $managedUser) }}" class="client-button-muted !px-3 !py-2 !text-xs">Modifier</a>
                                        @endif
                                        @if ($canDeleteUser && auth()->id() !== $managedUser->id)
                                            <form method="POST" action="{{ route('client.users.destroy', $managedUser) }}" onsubmit="return confirm('Supprimer cet utilisateur ?');">
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
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Aucun utilisateur trouve.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div>
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
