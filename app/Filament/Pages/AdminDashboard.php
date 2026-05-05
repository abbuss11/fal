<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class AdminDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -10;

    protected static ?string $slug = '';

    protected static string $view = 'filament.pages.admin-dashboard';

    /**
     * @var array<string, int|float>
     */
    public array $stats = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $projects = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $tasks = [];

    /**
     * @var array<string, int>
     */
    public array $statusBreakdown = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $trend = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $projectStatusBreakdown = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $riskProjects = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $activityFeed = [];

    public function mount(): void
    {
        $this->refreshData();
    }

    /**
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            'echo-private:admin.tasks,ProjectWorkspaceUpdated' => 'refreshData',
        ];
    }

    public function refreshData(): void
    {
        $totalTasks = Task::query()->count();
        $doneTasks = Task::query()->where('status', Task::STATUS_DONE)->count();

        $this->stats = [
            'users_total' => User::query()->count(),
            'projects_total' => Project::query()->count(),
            'projects_active' => Project::query()->whereIn('status', ['planning', 'active', 'on_hold'])->count(),
            'tasks_total' => $totalTasks,
            'tasks_done' => $doneTasks,
            'tasks_overdue' => Task::query()
                ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now())
                ->count(),
            'completion_rate' => $totalTasks > 0
                ? round(($doneTasks / $totalTasks) * 100, 1)
                : 0.0,
        ];

        $this->statusBreakdown = Task::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();

        $this->trend = collect(range(5, 0))
            ->map(function (int $monthOffset): array {
                $start = now()->startOfMonth()->subMonths($monthOffset);
                $end = (clone $start)->endOfMonth();

                return [
                    'label' => $start->format('M y'),
                    'projects' => Project::query()
                        ->whereBetween('created_at', [$start, $end])
                        ->count(),
                    'tasks_completed' => Task::query()
                        ->where('status', Task::STATUS_DONE)
                        ->whereBetween('completed_at', [$start, $end])
                        ->count(),
                ];
            })
            ->values()
            ->all();

        $projectStatuses = Project::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalProjects = max(Project::query()->count(), 1);
        $this->projectStatusBreakdown = collect(Project::statusOptions())
            ->map(function (string $label, string $status) use ($projectStatuses, $totalProjects): array {
                $count = (int) ($projectStatuses[$status] ?? 0);

                return [
                    'status' => $status,
                    'label' => $label,
                    'count' => $count,
                    'percent' => (int) round(($count / $totalProjects) * 100),
                ];
            })
            ->values()
            ->all();

        $this->projects = Project::query()
            ->with('owner')
            ->withCount([
                'tasks',
                'tasks as tasks_done_count' => fn ($query) => $query->where('status', Task::STATUS_DONE),
            ])
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(function (Project $project): array {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => strtoupper((string) $project->status),
                    'owner' => $project->owner?->name ?? 'Non defini',
                    'tasks_count' => (int) $project->tasks_count,
                    'tasks_done_count' => (int) $project->tasks_done_count,
                    'edit_url' => \App\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $project]),
                ];
            })
            ->values()
            ->all();

        $this->tasks = Task::query()
            ->with(['project', 'assignee'])
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(function (Task $task): array {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'project' => $task->project?->name ?? 'Projet non defini',
                    'assignee' => $task->assignee?->name ?? 'Non assigne',
                    'status' => Task::statusOptions()[$task->status] ?? $task->status,
                    'priority' => Task::priorityOptions()[$task->priority] ?? $task->priority,
                    'updated_at' => $task->updated_at?->format('d/m/Y H:i'),
                    'edit_url' => \App\Filament\Resources\TaskResource::getUrl('edit', ['record' => $task]),
                ];
            })
            ->values()
            ->all();

        $this->riskProjects = Project::query()
            ->with('owner')
            ->withCount([
                'tasks',
                'tasks as open_tasks_count' => fn ($query) => $query->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING]),
                'tasks as done_tasks_count' => fn ($query) => $query->where('status', Task::STATUS_DONE),
                'tasks as overdue_tasks_count' => fn ($query) => $query
                    ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()),
            ])
            ->whereIn('status', [Project::STATUS_PLANNING, Project::STATUS_ACTIVE, Project::STATUS_ON_HOLD])
            ->latest('updated_at')
            ->limit(12)
            ->get()
            ->filter(fn (Project $project): bool => (int) $project->overdue_tasks_count > 0)
            ->take(6)
            ->map(function (Project $project): array {
                $trackedTotal = max((int) $project->open_tasks_count + (int) $project->done_tasks_count, 1);

                return [
                    'name' => $project->name,
                    'owner' => $project->owner?->name ?? 'Non defini',
                    'overdue' => (int) $project->overdue_tasks_count,
                    'open' => (int) $project->open_tasks_count,
                    'health' => (int) round(((int) $project->done_tasks_count / $trackedTotal) * 100),
                    'edit_url' => \App\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $project]),
                ];
            })
            ->values()
            ->all();

        $this->activityFeed = ActivityLog::query()
            ->with(['user', 'task.project'])
            ->latest()
            ->limit(7)
            ->get()
            ->map(function (ActivityLog $log): array {
                $actor = $log->user?->name ?? 'Systeme';
                $taskTitle = $log->task?->title ?? 'Tache';
                $projectName = $log->task?->project?->name ?? 'Projet';
                $action = Str::headline((string) $log->action);

                return [
                    'actor' => $actor,
                    'action' => $action,
                    'task' => $taskTitle,
                    'project' => $projectName,
                    'time' => $log->created_at?->diffForHumans() ?? '-',
                    'task_url' => $log->task
                        ? \App\Filament\Resources\TaskResource::getUrl('edit', ['record' => $log->task])
                        : null,
                ];
            })
            ->values()
            ->all();
    }
}
