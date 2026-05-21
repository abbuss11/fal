<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission('teams.read'), 403);

        $search = trim($request->string('q')->toString());
        $activeFilter = $request->string('active')->toString();

        $baseQuery = $user->visibleTeamsQuery();

        $statsQuery = clone $baseQuery;
        $teamIds = (clone $statsQuery)->pluck('teams.id');
        $activeMemberIds = User::query()
            ->whereHas('teams', function (Builder $query) use ($teamIds): void {
                $query
                    ->whereIn('teams.id', $teamIds)
                    ->where('team_user.is_active', true);
            })
            ->pluck('users.id')
            ->unique();

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('is_active', true)->count(),
            'inactive' => (clone $statsQuery)->where('is_active', false)->count(),
            'owned' => (clone $statsQuery)->where('owner_id', $user->id)->count(),
            'open_tasks' => $activeMemberIds->isNotEmpty()
                ? Task::query()
                    ->whereIn('assigned_to', $activeMemberIds->all())
                    ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])
                    ->count()
                : 0,
            'members' => $activeMemberIds->count(),
        ];

        $teamsQuery = (clone $baseQuery)
            ->with('owner')
            ->withCount([
                'members',
                'members as active_members_count' => fn ($query) => $query->where('team_user.is_active', true),
                'members as managers_count' => fn ($query) => $query->whereIn('team_user.role', [User::ROLE_MANAGER, User::ROLE_PROJECT_MANAGER]),
            ]);

        if ($search !== '') {
            $teamsQuery->where(function (Builder $query) use ($search): void {
                $query
                    ->where('teams.name', 'like', "%{$search}%")
                    ->orWhere('teams.description', 'like', "%{$search}%")
                    ->orWhereHas('owner', fn (Builder $ownerQuery) => $ownerQuery->where('users.name', 'like', "%{$search}%"));
            });
        }

        if ($activeFilter === '1' || $activeFilter === '0') {
            $teamsQuery->where('is_active', $activeFilter === '1');
        }

        $teams = $teamsQuery
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('client.teams.index', [
            'teams' => $teams,
            'stats' => $stats,
            'canCreateTeam' => $user->hasPermission('teams.create'),
            'canUpdateTeam' => $user->hasPermission('teams.update'),
            'canDeleteTeam' => $user->hasPermission('teams.delete'),
        ]);
    }

    public function create(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission('teams.create'), 403);

        return view('client.teams.create', [
            'team' => new Team(),
            'members' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']),
            'ownerOptions' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission('teams.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $ownerId = $user->isAdmin() && isset($validated['owner_id'])
            ? (int) $validated['owner_id']
            : $user->id;

        $team = Team::query()->create([
            'owner_id' => $ownerId,
            'name' => trim((string) $validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->syncMembers(
            $team,
            $ownerId,
            array_map(static fn (mixed $id): int => (int) $id, (array) ($validated['member_ids'] ?? [])),
        );

        return redirect()
            ->route('client.teams.show', $team)
            ->with('status', 'Equipe creee avec succes.');
    }

    public function show(Request $request, Team $team): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission('teams.read'), 403);
        abort_unless($user->canAccessTeam($team), 403);

        $team->load([
            'owner',
            'members' => fn ($query) => $query->orderBy('name')->withPivot(['role', 'is_active']),
        ]);

        $activeMemberIds = $team->members
            ->where('pivot.is_active', true)
            ->pluck('id')
            ->values();

        $stats = [
            'members_total' => $team->members->count(),
            'members_active' => $team->members->where('pivot.is_active', true)->count(),
            'managers_total' => $team->members->whereIn('pivot.role', [User::ROLE_MANAGER, User::ROLE_PROJECT_MANAGER])->count(),
            'tasks_open' => $activeMemberIds->isNotEmpty()
                ? Task::query()
                    ->whereIn('assigned_to', $activeMemberIds->all())
                    ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])
                    ->count()
                : 0,
            'tasks_overdue' => $activeMemberIds->isNotEmpty()
                ? Task::query()
                    ->whereIn('assigned_to', $activeMemberIds->all())
                    ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now())
                    ->count()
                : 0,
            'projects_covered' => $activeMemberIds->isNotEmpty()
                ? Project::query()
                    ->where(function (Builder $query) use ($activeMemberIds): void {
                        $query
                            ->whereIn('owner_id', $activeMemberIds->all())
                            ->orWhereHas('members', function (Builder $memberQuery) use ($activeMemberIds): void {
                                $memberQuery
                                    ->whereIn('users.id', $activeMemberIds->all())
                                    ->where('project_user.is_active', true);
                            });
                    })
                    ->count()
                : 0,
        ];

        $availableMembers = User::query()
            ->where('is_active', true)
            ->whereNotIn('id', $team->members->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('client.teams.show', [
            'team' => $team,
            'stats' => $stats,
            'availableMembers' => $availableMembers,
            'roleOptions' => [
                User::ROLE_PROJECT_MANAGER => 'Chef de projet',
                User::ROLE_MANAGER => 'Manager',
                User::ROLE_MEMBER => 'Membre',
                User::ROLE_CLIENT => 'Client',
            ],
            'canManageTeam' => $user->hasPermission('teams.manage_members') && $user->canManageTeam($team),
        ]);
    }

    public function edit(Request $request, Team $team): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission('teams.update'), 403);
        abort_unless($user->canManageTeam($team), 403);

        $team->load([
            'members' => fn ($query) => $query->withPivot(['role', 'is_active'])->orderBy('name'),
        ]);

        return view('client.teams.edit', [
            'team' => $team,
            'members' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']),
            'ownerOptions' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission('teams.update'), 403);
        abort_unless($user->canManageTeam($team), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $ownerId = $user->isAdmin() && isset($validated['owner_id'])
            ? (int) $validated['owner_id']
            : $team->owner_id;

        if (! $user->isAdmin()) {
            $ownerId = $team->owner_id;
        }

        $team->update([
            'owner_id' => $ownerId,
            'name' => trim((string) $validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->syncMembers(
            $team,
            $ownerId,
            array_map(static fn (mixed $id): int => (int) $id, (array) ($validated['member_ids'] ?? [])),
        );

        return redirect()
            ->route('client.teams.show', $team)
            ->with('status', 'Equipe mise a jour.');
    }

    public function destroy(Request $request, Team $team): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission('teams.delete'), 403);
        abort_unless($user->canManageTeam($team), 403);

        $teamName = $team->name;
        $team->delete();

        return redirect()
            ->route('client.teams.index')
            ->with('status', "Equipe \"{$teamName}\" supprimee.");
    }

    public function storeMember(Request $request, Team $team): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission('teams.manage_members'), 403);
        abort_unless($user->canManageTeam($team), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', Rule::in([User::ROLE_PROJECT_MANAGER, User::ROLE_MANAGER, User::ROLE_MEMBER, User::ROLE_CLIENT])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $team->members()->syncWithoutDetaching([
            (int) $validated['user_id'] => [
                'role' => (string) $validated['role'],
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ],
        ]);

        return back()->with('status', 'Membre ajoute a l equipe.');
    }

    public function updateMember(Request $request, Team $team, User $member): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission('teams.manage_members'), 403);
        abort_unless($user->canManageTeam($team), 403);
        abort_unless($team->members()->where('users.id', $member->id)->exists(), 404);

        $validated = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_PROJECT_MANAGER, User::ROLE_MANAGER, User::ROLE_MEMBER, User::ROLE_CLIENT])],
            'is_active' => ['required', 'boolean'],
        ]);

        $team->members()->updateExistingPivot($member->id, [
            'role' => (string) $validated['role'],
            'is_active' => (bool) $validated['is_active'],
        ]);

        return back()->with('status', 'Membre mis a jour.');
    }

    public function removeMember(Request $request, Team $team, User $member): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission('teams.manage_members'), 403);
        abort_unless($user->canManageTeam($team), 403);

        if ($member->id === (int) $team->owner_id) {
            abort(422, 'Le proprietaire de l equipe ne peut pas etre retire.');
        }

        $team->members()->detach($member->id);

        return back()->with('status', 'Membre retire de l equipe.');
    }

    /**
     * @param array<int, int> $memberIds
     */
    private function syncMembers(Team $team, int $ownerId, array $memberIds): void
    {
        $ids = collect($memberIds)
            ->push($ownerId)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $payload = $ids
            ->mapWithKeys(function (int $userId) use ($ownerId): array {
                return [
                    $userId => [
                        'role' => $userId === $ownerId ? User::ROLE_PROJECT_MANAGER : User::ROLE_MEMBER,
                        'is_active' => true,
                    ],
                ];
            })
            ->all();

        $team->members()->sync($payload);
    }
}
