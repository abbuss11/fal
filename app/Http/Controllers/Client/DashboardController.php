<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $data = $this->buildDashboardData($user);

        return view('client.dashboard', $data + [
            'roleLabel' => User::roleOptions()[$user->role] ?? 'Membre',
        ]);
    }

    public function snapshot(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($this->buildDashboardData($user));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDashboardData(User $user): array
    {
        $projectsQuery = $user->visibleProjectsQuery();
        $tasksQuery = $user->visibleTasksQuery();
        $teamsQuery = $user->visibleTeamsQuery();
        $visibleProjectIds = (clone $projectsQuery)->pluck('projects.id')->unique()->values();
        $visibleUserIds = $this->visibleUserIds($user);
        $visibleUsersQuery = User::query()->whereIn('id', $visibleUserIds->all());

        $statusBreakdown = [
            Task::STATUS_TODO => (clone $tasksQuery)
                ->where('status', Task::STATUS_TODO)
                ->count(),
            Task::STATUS_DOING => (clone $tasksQuery)
                ->where('status', Task::STATUS_DOING)
                ->count(),
            Task::STATUS_DONE => (clone $tasksQuery)
                ->where('status', Task::STATUS_DONE)
                ->count(),
        ];

        $timesheetHoursWeek = 0.0;
        if ($user->hasPermission('timesheets.read')) {
            $timesheetQuery = Timesheet::query()
                ->whereBetween('work_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()]);

            if ($user->isAdmin() || $user->hasPermission('timesheets.update')) {
                if ($visibleProjectIds->isNotEmpty()) {
                    $timesheetQuery->whereIn('project_id', $visibleProjectIds->all());
                } else {
                    $timesheetQuery->whereRaw('1 = 0');
                }
            } else {
                $timesheetQuery->where('user_id', $user->id);
            }

            $timesheetHoursWeek = (float) $timesheetQuery->sum('hours');
        }

        $stats = [
            'projects_total' => (clone $projectsQuery)->count(),
            'projects_active' => (clone $projectsQuery)
                ->whereIn('status', ['planning', 'active', 'on_hold'])
                ->count(),
            'tasks_total' => (clone $tasksQuery)->count(),
            'tasks_done' => (clone $tasksQuery)
                ->where('status', Task::STATUS_DONE)
                ->count(),
            'tasks_overdue' => (clone $tasksQuery)
                ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now())
                ->count(),
            'tasks_due_week' => (clone $tasksQuery)
                ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [now()->startOfDay(), now()->copy()->addDays(7)->endOfDay()])
                ->count(),
            'my_open_tasks' => Task::query()
                ->where('assigned_to', $user->id)
                ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])
                ->count(),
            'notifications_unread' => $user->unreadNotifications()->count(),
            'users_total' => $user->hasPermission('users.read') ? (clone $visibleUsersQuery)->count() : 0,
            'users_active' => $user->hasPermission('users.read')
                ? (clone $visibleUsersQuery)->where('is_active', true)->count()
                : 0,
            'teams_total' => $user->hasPermission('teams.read') ? (clone $teamsQuery)->count() : 0,
            'teams_active' => $user->hasPermission('teams.read')
                ? (clone $teamsQuery)->where('is_active', true)->count()
                : 0,
            'timesheet_hours_week' => round($timesheetHoursWeek, 2),
        ];

        $velocity = collect(range(6, 0))
            ->map(function (int $daysAgo) use ($tasksQuery): array {
                $date = now()->subDays($daysAgo);

                return [
                    'label' => $date->format('d/m'),
                    'value' => (clone $tasksQuery)
                        ->whereNotNull('completed_at')
                        ->whereDate('completed_at', $date->toDateString())
                        ->count(),
                ];
            })
            ->values()
            ->all();

        $projects = (clone $projectsQuery)
            ->with(['owner'])
            ->withCount([
                'tasks',
                'tasks as tasks_done_count' => fn ($query) => $query->where('status', Task::STATUS_DONE),
            ])
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $projectLoad = (clone $projectsQuery)
            ->withCount([
                'tasks as open_tasks_count' => fn ($query) => $query->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING]),
                'tasks as overdue_tasks_count' => fn ($query) => $query
                    ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()),
                'tasks as done_tasks_count' => fn ($query) => $query->where('status', Task::STATUS_DONE),
            ])
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function (Project $project): array {
                $openTasks = (int) $project->open_tasks_count;
                $doneTasks = (int) $project->done_tasks_count;
                $totalTracked = max($openTasks + $doneTasks, 1);

                return [
                    'name' => $project->name,
                    'status' => strtoupper((string) $project->status),
                    'open_tasks' => $openTasks,
                    'overdue_tasks' => (int) $project->overdue_tasks_count,
                    'completion' => (int) round(($doneTasks / $totalTracked) * 100),
                    'url' => route('client.projects.show', $project),
                ];
            })
            ->values()
            ->all();

        $myTasks = Task::query()
            ->with('project')
            ->where('assigned_to', $user->id)
            ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        $projectsPreview = $projects
            ->map(function ($project): array {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'owner' => $project->owner?->name ?? 'Non defini',
                    'status' => strtoupper((string) $project->status),
                    'tasks_count' => (int) $project->tasks_count,
                    'tasks_done_count' => (int) $project->tasks_done_count,
                    'url' => route('client.projects.show', $project),
                ];
            })
            ->values()
            ->all();

        $tasksPreview = $myTasks
            ->map(function (Task $task): array {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'project' => $task->project?->name ?? 'Projet non defini',
                    'due_date' => $task->due_date?->format('d/m/Y H:i') ?? 'Aucune',
                ];
            })
            ->values()
            ->all();

        $usersPreview = [];
        if ($user->hasPermission('users.read')) {
            $usersPreview = (clone $visibleUsersQuery)
                ->where('is_active', true)
                ->withCount([
                    'assignedTasks as open_tasks_count' => fn ($query) => $query->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING]),
                    'teams as active_teams_count' => fn ($query) => $query->where('team_user.is_active', true),
                ])
                ->orderByDesc('last_seen_at')
                ->limit(5)
                ->get()
                ->map(function (User $member): array {
                    return [
                        'name' => $member->name,
                        'role' => User::roleOptions()[$member->role] ?? strtoupper($member->role),
                        'open_tasks' => (int) $member->open_tasks_count,
                        'active_teams' => (int) $member->active_teams_count,
                        'last_seen_at' => $member->last_seen_at?->diffForHumans() ?? 'N/A',
                    ];
                })
                ->values()
                ->all();
        }

        $teamsPreview = [];
        if ($user->hasPermission('teams.read')) {
            $teamsPreview = (clone $teamsQuery)
                ->with('owner')
                ->withCount([
                    'members as active_members_count' => fn ($query) => $query->where('team_user.is_active', true),
                ])
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->map(function (Team $team): array {
                    return [
                        'name' => $team->name,
                        'owner' => $team->owner?->name ?? 'N/A',
                        'active_members' => (int) $team->active_members_count,
                        'status' => $team->is_active ? 'ACTIVE' : 'INACTIVE',
                        'url' => route('client.teams.show', $team),
                    ];
                })
                ->values()
                ->all();
        }

        return [
            'stats' => $stats,
            'projects' => $projects,
            'myTasks' => $myTasks,
            'projectsPreview' => $projectsPreview,
            'tasksPreview' => $tasksPreview,
            'usersPreview' => $usersPreview,
            'teamsPreview' => $teamsPreview,
            'statusBreakdown' => $statusBreakdown,
            'velocity' => $velocity,
            'projectLoad' => $projectLoad,
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function visibleUserIds(User $user): Collection
    {
        if ($user->isAdmin()) {
            return User::query()->pluck('id');
        }

        return User::query()
            ->where('role', '!=', User::ROLE_ADMIN)
            ->pluck('id');
    }
}
