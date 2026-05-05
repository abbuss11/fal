<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'my_open_tasks' => Task::query()
                ->where('assigned_to', $user->id)
                ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])
                ->count(),
            'notifications_unread' => $user->unreadNotifications()->count(),
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

        return [
            'stats' => $stats,
            'projects' => $projects,
            'myTasks' => $myTasks,
            'projectsPreview' => $projectsPreview,
            'tasksPreview' => $tasksPreview,
            'statusBreakdown' => $statusBreakdown,
            'velocity' => $velocity,
            'projectLoad' => $projectLoad,
            'updated_at' => now()->toDateTimeString(),
        ];
    }
}
