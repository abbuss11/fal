<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ProjectReportService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Project $project): array
    {
        $project->loadMissing([
            'owner',
            'client',
            'members',
            'tasks.assignee',
            'tasks.tags',
            'tasks.dependencies',
            'timesheets.user',
        ]);

        $tasks = $project->tasks;
        $members = $project->members;
        $timesheets = $project->timesheets;

        if ($project->owner && ! $members->contains('id', $project->owner->id)) {
            $members = $members->push($project->owner);
        }
        $activityLogs = $project->activityLogs()
            ->with(['user', 'task'])
            ->latest()
            ->limit(25)
            ->get();

        $commentsCount = $project->comments()->count();
        $dependenciesCount = $tasks->sum(fn (Task $task): int => $task->dependencies->count());
        $tagsUsed = $tasks
            ->flatMap(fn (Task $task) => $task->tags->pluck('name'))
            ->unique()
            ->values()
            ->all();

        $statusBreakdown = $this->statusBreakdown($tasks);
        $priorityBreakdown = $this->priorityBreakdown($tasks);

        $totalTasks = $tasks->count();
        $doneTasks = (int) ($statusBreakdown[Task::STATUS_DONE] ?? 0);
        $progressRate = $totalTasks > 0
            ? round(($doneTasks / $totalTasks) * 100, 1)
            : 0.0;

        $overdueTasks = $tasks
            ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])
            ->filter(fn (Task $task): bool => $task->due_date && $task->due_date->isPast())
            ->count();

        $averageCompletionHours = $this->averageCompletionHours($tasks);
        $velocityLastWeeks = $this->velocityLastWeeks($tasks, 6);

        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'is_archived' => (bool) $project->is_archived,
                'is_template' => (bool) $project->is_template,
                'template_name' => $project->template_name,
                'priority' => $project->priority,
                'description' => $project->description,
                'owner' => $project->owner?->name,
                'client' => $project->client?->name,
                'start_date' => $project->start_date?->toDateString(),
                'due_date' => $project->due_date?->toDateString(),
            ],
            'stats' => [
                'members_total' => $members->count(),
                'members_active' => $members->filter(
                    fn (User $member): bool => (bool) ($member->pivot->is_active ?? true)
                )->count(),
                'tasks_total' => $totalTasks,
                'tasks_done' => $doneTasks,
                'tasks_overdue' => $overdueTasks,
                'comments_total' => $commentsCount,
                'progress_rate' => $progressRate,
                'average_completion_hours' => $averageCompletionHours,
                'logged_hours' => round((float) $timesheets->sum('hours'), 2),
                'dependencies_total' => $dependenciesCount,
                'tags_total' => count($tagsUsed),
            ],
            'status_breakdown' => $statusBreakdown,
            'priority_breakdown' => $priorityBreakdown,
            'member_workload' => $this->memberWorkload($project, $members, $tasks),
            'team_performance' => $this->teamPerformance($members, $tasks, $timesheets),
            'tags_used' => $tagsUsed,
            'timeline' => $this->timeline($activityLogs),
            'velocity_last_weeks' => $velocityLastWeeks,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @param Collection<int, Task> $tasks
     * @return array<string, int>
     */
    private function statusBreakdown(Collection $tasks): array
    {
        $counts = $tasks->countBy(fn (Task $task): string => $task->normalized_status);

        return [
            Task::STATUS_TODO => (int) ($counts[Task::STATUS_TODO] ?? 0),
            Task::STATUS_DOING => (int) ($counts[Task::STATUS_DOING] ?? 0),
            Task::STATUS_DONE => (int) ($counts[Task::STATUS_DONE] ?? 0),
        ];
    }

    /**
     * @param Collection<int, Task> $tasks
     * @return array<string, int>
     */
    private function priorityBreakdown(Collection $tasks): array
    {
        $counts = $tasks->countBy(fn (Task $task): string => (string) $task->priority);

        return [
            Task::PRIORITY_LOW => (int) ($counts[Task::PRIORITY_LOW] ?? 0),
            Task::PRIORITY_MEDIUM => (int) ($counts[Task::PRIORITY_MEDIUM] ?? 0),
            Task::PRIORITY_HIGH => (int) ($counts[Task::PRIORITY_HIGH] ?? 0),
            Task::PRIORITY_URGENT => (int) ($counts[Task::PRIORITY_URGENT] ?? 0),
        ];
    }

    /**
     * @param Collection<int, Task> $tasks
     */
    private function averageCompletionHours(Collection $tasks): float
    {
        $completed = $tasks
            ->filter(fn (Task $task): bool => $task->completed_at !== null)
            ->values();

        if ($completed->isEmpty()) {
            return 0.0;
        }

        $totalHours = $completed->reduce(function (int $carry, Task $task): int {
            if (! $task->created_at || ! $task->completed_at) {
                return $carry;
            }

            return $carry + $task->created_at->diffInHours($task->completed_at);
        }, 0);

        return round($totalHours / $completed->count(), 1);
    }

    /**
     * @param Collection<int, Task> $tasks
     * @return array<int, array<string, mixed>>
     */
    private function velocityLastWeeks(Collection $tasks, int $weeks): array
    {
        $result = [];

        for ($offset = $weeks - 1; $offset >= 0; $offset--) {
            $start = now()->startOfWeek()->subWeeks($offset);
            $end = (clone $start)->endOfWeek();

            $doneCount = $tasks
                ->filter(function (Task $task) use ($start, $end): bool {
                    if (! $task->completed_at) {
                        return false;
                    }

                    return $task->completed_at->between($start, $end);
                })
                ->count();

            $result[] = [
                'label' => $start->format('d M').' - '.$end->format('d M'),
                'done' => $doneCount,
            ];
        }

        return $result;
    }

    /**
     * @param Collection<int, User> $members
     * @param Collection<int, Task> $tasks
     * @return array<int, array<string, mixed>>
     */
    private function memberWorkload(Project $project, Collection $members, Collection $tasks): array
    {
        return $members
            ->map(function (User $member) use ($project, $tasks): array {
                $assigned = $tasks->where('assigned_to', $member->id);
                $done = $assigned->where('status', Task::STATUS_DONE)->count();
                $open = $assigned->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])->count();

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'project_role' => (string) ($member->pivot->role ?? ($project->owner_id === $member->id ? User::ROLE_PROJECT_MANAGER : User::ROLE_MEMBER)),
                    'active' => (bool) ($member->pivot->is_active ?? true),
                    'is_attached' => (bool) isset($member->pivot),
                    'last_seen_at' => $member->last_seen_at instanceof CarbonInterface
                        ? $member->last_seen_at->toDateTimeString()
                        : null,
                    'tasks_assigned' => $assigned->count(),
                    'tasks_open' => $open,
                    'tasks_done' => $done,
                ];
            })
            ->sortByDesc('tasks_assigned')
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, \App\Models\ActivityLog> $activityLogs
     * @return array<int, array<string, mixed>>
     */
    private function timeline(Collection $activityLogs): array
    {
        return $activityLogs
            ->map(function ($log): array {
                return [
                    'date' => $log->created_at?->format('d/m/Y H:i'),
                    'action' => (string) $log->action,
                    'actor' => $log->user?->name ?? 'Systeme',
                    'task' => $log->task?->title ?? null,
                    'meta' => $log->meta ?? [],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, User> $members
     * @param Collection<int, Task> $tasks
     * @param Collection<int, \App\Models\Timesheet> $timesheets
     * @return array<int, array<string, mixed>>
     */
    private function teamPerformance(Collection $members, Collection $tasks, Collection $timesheets): array
    {
        return $members
            ->map(function (User $member) use ($tasks, $timesheets): array {
                $assignedTasks = $tasks->where('assigned_to', $member->id);
                $tasksDone = $assignedTasks->where('status', Task::STATUS_DONE)->count();
                $hours = (float) $timesheets->where('user_id', $member->id)->sum('hours');
                $efficiency = $hours > 0 ? round($tasksDone / $hours, 3) : 0.0;

                return [
                    'user_id' => $member->id,
                    'name' => $member->name,
                    'tasks_done' => $tasksDone,
                    'logged_hours' => round($hours, 2),
                    'efficiency' => $efficiency,
                ];
            })
            ->sortByDesc('efficiency')
            ->values()
            ->all();
    }
}
