<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser->hasPermission('users.read'), 403);

        $roleFilter = $request->string('role')->toString();
        $activeFilter = $request->string('active')->toString();
        $search = trim($request->string('q')->toString());

        $baseQuery = User::query();
        if (! $currentUser->isAdmin()) {
            $baseQuery->where('role', '!=', User::ROLE_ADMIN);
        }

        $statsQuery = clone $baseQuery;
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('is_active', true)->count(),
            'inactive' => (clone $statsQuery)->where('is_active', false)->count(),
            'managers' => (clone $statsQuery)
                ->whereIn('role', [User::ROLE_MANAGER, User::ROLE_PROJECT_MANAGER])
                ->count(),
            'members' => (clone $statsQuery)->where('role', User::ROLE_MEMBER)->count(),
            'clients' => (clone $statsQuery)->where('role', User::ROLE_CLIENT)->count(),
        ];

        $usersQuery = clone $baseQuery;

        if ($search !== '') {
            $usersQuery->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%");
            });
        }

        if ($roleFilter !== '' && array_key_exists($roleFilter, User::roleOptions())) {
            $usersQuery->where('role', $roleFilter);
        }

        if ($activeFilter === '1' || $activeFilter === '0') {
            $usersQuery->where('is_active', $activeFilter === '1');
        }

        $users = $usersQuery
            ->withCount([
                'ownedProjects',
                'assignedTasks as open_tasks_count' => fn ($query) => $query->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING]),
                'teams as active_teams_count' => fn ($query) => $query->where('team_user.is_active', true),
            ])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('client.users.index', [
            'users' => $users,
            'stats' => $stats,
            'roleOptions' => User::roleOptions(),
            'canCreateUser' => $currentUser->hasPermission('users.create'),
            'canUpdateUser' => $currentUser->hasPermission('users.update'),
            'canDeleteUser' => $currentUser->hasPermission('users.delete'),
        ]);
    }

    public function create(Request $request): View
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser->hasPermission('users.create'), 403);

        return view('client.users.create', [
            'userModel' => new User(),
            'roleOptions' => User::roleOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser->hasPermission('users.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(array_keys(User::roleOptions()))],
            'is_active' => ['nullable', 'boolean'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:60'],
            'bio' => ['nullable', 'string'],
            'notify_email' => ['nullable', 'boolean'],
            'notify_realtime' => ['nullable', 'boolean'],
            'notify_push' => ['nullable', 'boolean'],
        ]);

        User::query()->create([
            'name' => trim((string) $validated['name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'password' => Hash::make((string) $validated['password']),
            'role' => $this->resolvedRole($currentUser, (string) $validated['role']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'job_title' => $validated['job_title'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'notify_email' => (bool) ($validated['notify_email'] ?? true),
            'notify_realtime' => (bool) ($validated['notify_realtime'] ?? true),
            'notify_push' => (bool) ($validated['notify_push'] ?? true),
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('client.users.index')
            ->with('status', 'Utilisateur cree avec succes.');
    }

    public function edit(Request $request, User $user): View
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser->hasPermission('users.update'), 403);

        $this->ensureUserIsManageable($currentUser, $user);

        return view('client.users.edit', [
            'userModel' => $user,
            'roleOptions' => User::roleOptions(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser->hasPermission('users.update'), 403);

        $this->ensureUserIsManageable($currentUser, $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(array_keys(User::roleOptions()))],
            'is_active' => ['nullable', 'boolean'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:60'],
            'bio' => ['nullable', 'string'],
            'notify_email' => ['nullable', 'boolean'],
            'notify_realtime' => ['nullable', 'boolean'],
            'notify_push' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => trim((string) $validated['name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'role' => $this->resolvedRole($currentUser, (string) $validated['role']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'job_title' => $validated['job_title'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'notify_email' => (bool) ($validated['notify_email'] ?? true),
            'notify_realtime' => (bool) ($validated['notify_realtime'] ?? true),
            'notify_push' => (bool) ($validated['notify_push'] ?? true),
        ];

        if ($user->id === $currentUser->id && ! $currentUser->isAdmin()) {
            $payload['is_active'] = true;
        }

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make((string) $validated['password']);
        }

        $user->update($payload);

        return redirect()
            ->route('client.users.index')
            ->with('status', 'Utilisateur mis a jour.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser->hasPermission('users.delete'), 403);

        if ($user->id === $currentUser->id) {
            abort(422, 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $this->ensureUserIsManageable($currentUser, $user);

        $userName = $user->name;
        $user->delete();

        return redirect()
            ->route('client.users.index')
            ->with('status', "Utilisateur \"{$userName}\" supprime.");
    }

    private function resolvedRole(User $actor, string $requestedRole): string
    {
        if (! array_key_exists($requestedRole, User::roleOptions())) {
            return User::ROLE_MEMBER;
        }

        if (! $actor->isAdmin() && $requestedRole === User::ROLE_ADMIN) {
            return User::ROLE_MEMBER;
        }

        return $requestedRole;
    }

    private function ensureUserIsManageable(User $actor, User $target): void
    {
        if ($actor->isAdmin()) {
            return;
        }

        if ($target->isAdmin()) {
            abort(403);
        }
    }
}
