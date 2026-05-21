<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="fal-brand-kicker">Team workspace</p>
                <h1 class="mt-1 text-2xl font-semibold client-heading-accent">{{ $team->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">Owner: {{ $team->owner?->name ?? 'N/A' }} | Etat: {{ $team->is_active ? 'Active' : 'Inactive' }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('client.teams.index') }}" class="client-button-muted">Retour equipes</a>
                @if ($canManageTeam)
                    <a href="{{ route('client.teams.edit', $team) }}" class="client-button">Modifier equipe</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="client-shell space-y-5">
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <article class="client-stat xl:col-span-1">
                <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Membres</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $stats['members_total'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Total rattache</p>
            </article>
            <article class="client-stat xl:col-span-1">
                <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Actifs</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $stats['members_active'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Disponibles</p>
            </article>
            <article class="client-stat xl:col-span-1">
                <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Managers</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $stats['managers_total'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Leadership</p>
            </article>
            <article class="client-stat xl:col-span-1">
                <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Taches ouvertes</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $stats['tasks_open'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Todo + Doing</p>
            </article>
            <article class="client-stat xl:col-span-1">
                <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Retards</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $stats['tasks_overdue'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Priorite immediate</p>
            </article>
            <article class="client-stat xl:col-span-1">
                <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Projets couverts</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $stats['projects_covered'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Impact delivery</p>
            </article>
        </section>

        @if ($canManageTeam)
            <section class="client-panel p-5">
                <h2 class="saas-panel-title">Ajouter un membre</h2>
                <form method="POST" action="{{ route('client.teams.members.store', $team) }}" class="mt-3 grid gap-3 md:grid-cols-4">
                    @csrf
                    <label class="block md:col-span-2">
                        <span class="saas-label">Utilisateur</span>
                        <select name="user_id" class="saas-field" required>
                            <option value="">Selectionner...</option>
                            @foreach ($availableMembers as $availableMember)
                                <option value="{{ $availableMember->id }}">{{ $availableMember->name }} ({{ $availableMember->email }})</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="saas-label">Role</span>
                        <select name="role" class="saas-field" required>
                            @foreach ($roleOptions as $roleCode => $roleLabel)
                                <option value="{{ $roleCode }}">{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex items-end">
                        <button type="submit" class="client-button w-full">Ajouter</button>
                    </div>
                </form>
            </section>
        @endif

        <section class="saas-table">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--client-line)]">
                    <thead class="saas-table-head">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Membre</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Role equipe</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Derniere activite</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--client-line)] bg-white">
                        @forelse ($team->members as $member)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $member->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $member->email }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $roleOptions[$member->pivot->role] ?? strtoupper((string) $member->pivot->role) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $member->pivot->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $member->pivot->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $member->last_seen_at?->diffForHumans() ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    @if ($canManageTeam)
                                        <div class="flex justify-end gap-2">
                                            <form method="POST" action="{{ route('client.teams.members.update', [$team, $member]) }}" class="flex items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="role" class="rounded-lg border-[var(--client-line)] bg-white px-2 py-1 text-xs">
                                                    @foreach ($roleOptions as $roleCode => $roleLabel)
                                                        <option value="{{ $roleCode }}" @selected($member->pivot->role === $roleCode)>{{ $roleLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <select name="is_active" class="rounded-lg border-[var(--client-line)] bg-white px-2 py-1 text-xs">
                                                    <option value="1" @selected($member->pivot->is_active)>Actif</option>
                                                    <option value="0" @selected(! $member->pivot->is_active)>Inactif</option>
                                                </select>
                                                <button type="submit" class="client-button-muted !px-3 !py-1.5 !text-xs">Maj</button>
                                            </form>

                                            @if ((int) $member->id !== (int) $team->owner_id)
                                                <form method="POST" action="{{ route('client.teams.members.remove', [$team, $member]) }}" onsubmit="return confirm('Retirer ce membre ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="client-button-muted !px-3 !py-1.5 !text-xs !border-rose-200 !text-rose-700">Retirer</button>
                                                </form>
                                            @endif
                                        </div>
                                    @else
                                        <p class="text-right text-xs text-slate-400">Lecture seule</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Aucun membre dans cette equipe.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
