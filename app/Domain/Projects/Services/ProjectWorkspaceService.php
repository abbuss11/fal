<?php

namespace App\Domain\Projects\Services;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectMessage;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\ProjectReportService;
use Illuminate\Support\Collection;

class ProjectWorkspaceService
{
    public function __construct(private readonly ProjectReportService $reportService) {}

    /**
     * @param Collection<int, Task>|null $preloadedTasks
     * @param Collection<int, TaskComment>|null $preloadedComments
     * @return array<string, mixed>
     */
    public function buildSnapshot(
        Project $project,
        ?Collection $preloadedTasks = null,
        ?array $overview = null,
        ?Collection $preloadedComments = null,
    ): array {
        $tasks = $preloadedTasks ?? $project->tasks()
            ->with(['assignee', 'subtasks'])
            ->orderBy('position')
            ->orderBy('due_date')
            ->get();

        $overview ??= $this->reportService->build($project);
        $statusCounts = $overview['status_breakdown'] ?? [];

        $comments = $preloadedComments ?? $project->comments()
            ->with(['user', 'task'])
            ->latest()
            ->limit(12)
            ->get();

        $messages = $project->messages()
            ->with('user')
            ->latest()
            ->limit(25)
            ->get()
            ->reverse()
            ->values();

        $files = $project->files()
            ->with(['uploader', 'task'])
            ->latest()
            ->limit(25)
            ->get();

        $timeline = $project->activityLogs()
            ->with(['user', 'task'])
            ->latest()
            ->limit(12)
            ->get()
            ->map(function ($event): array {
                return [
                    'date' => $event->created_at?->format('d/m/Y H:i'),
                    'action' => (string) $event->action,
                    'actor' => $event->user?->name ?? 'Systeme',
                    'task' => $event->task?->title,
                    'meta' => $event->meta ?? [],
                ];
            })
            ->values()
            ->all();

        return [
            'version' => $this->snapshotVersion($project),
            'project_id' => $project->id,
            'status_counts' => [
                Task::STATUS_TODO => (int) ($statusCounts[Task::STATUS_TODO] ?? 0),
                Task::STATUS_DOING => (int) ($statusCounts[Task::STATUS_DOING] ?? 0),
                'review' => (int) $tasks
                    ->filter(fn (Task $task): bool => $task->normalized_status === Task::STATUS_DOING && (bool) $task->is_in_review)
                    ->count(),
                Task::STATUS_DONE => (int) ($statusCounts[Task::STATUS_DONE] ?? 0),
            ],
            'board_columns' => $this->buildBoardColumns($tasks),
            'stats' => $overview['stats'] ?? [],
            'timeline' => $timeline,
            'recent_comments' => $comments
                ->map(function (TaskComment $comment): array {
                    return [
                        'author' => $comment->user?->name ?? 'Systeme',
                        'task' => $comment->task?->title ?? 'Tache',
                        'body' => (string) $comment->body,
                        'created_at' => $comment->created_at?->format('d/m/Y H:i'),
                    ];
                })
                ->values()
                ->all(),
            'recent_messages' => $messages
                ->map(function (ProjectMessage $message): array {
                    return [
                        'author' => $message->user?->name ?? 'Systeme',
                        'body' => (string) $message->body,
                        'created_at' => $message->created_at?->format('d/m/Y H:i'),
                    ];
                })
                ->values()
                ->all(),
            'files' => $files
                ->map(function (ProjectFile $file) use ($project): array {
                    return [
                        'id' => $file->id,
                        'name' => (string) $file->original_name,
                        'version' => (int) $file->version,
                        'size' => (int) $file->size,
                        'uploader' => $file->uploader?->name ?? 'Systeme',
                        'task' => $file->task?->title,
                        'created_at' => $file->created_at?->format('d/m/Y H:i'),
                        'download_url' => route('client.projects.files.download', [$project, $file]),
                    ];
                })
                ->values()
                ->all(),
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    public function snapshotVersion(Project $project): string
    {
        $taskTimestamp = $project->tasks()->max('updated_at');
        $commentTimestamp = TaskComment::query()
            ->whereHas('task', fn ($query) => $query->where('project_id', $project->id))
            ->max('updated_at');
        $memberPivotTimestamp = $project->members()->max('project_user.updated_at');
        $messageTimestamp = ProjectMessage::query()
            ->where('project_id', $project->id)
            ->max('updated_at');
        $fileTimestamp = ProjectFile::query()
            ->where('project_id', $project->id)
            ->max('updated_at');
        $subtaskTimestamp = Task::query()
            ->where('project_id', $project->id)
            ->withMax('subtasks', 'updated_at')
            ->get()
            ->max('subtasks_max_updated_at');

        return sha1(implode('|', [
            $project->updated_at?->timestamp ?? 0,
            $taskTimestamp ? strtotime((string) $taskTimestamp) : 0,
            $commentTimestamp ? strtotime((string) $commentTimestamp) : 0,
            $memberPivotTimestamp ? strtotime((string) $memberPivotTimestamp) : 0,
            $messageTimestamp ? strtotime((string) $messageTimestamp) : 0,
            $fileTimestamp ? strtotime((string) $fileTimestamp) : 0,
            $subtaskTimestamp ? strtotime((string) $subtaskTimestamp) : 0,
        ]));
    }

    /**
     * @param Collection<int, Task> $tasks
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function buildBoardColumns(Collection $tasks): array
    {
        $columns = [
            Task::STATUS_TODO => [],
            Task::STATUS_DOING => [],
            'review' => [],
            Task::STATUS_DONE => [],
        ];

        foreach ($tasks as $task) {
            $status = $task->board_lane;

            if (! array_key_exists($status, $columns)) {
                continue;
            }

            $columns[$status][] = [
                'id' => $task->id,
                'title' => $task->title,
                'assignee' => $task->assignee?->name ?? 'Non assigne',
                'status' => $status,
                'priority' => (string) $task->priority,
                'priority_label' => Task::priorityOptions()[$task->priority] ?? ucfirst((string) $task->priority),
                'due_date' => $task->due_date?->format('d/m/Y H:i') ?? 'Aucune',
                'due_date_iso' => $task->due_date?->toIso8601String(),
                'position' => (int) $task->position,
                'subtasks_total' => (int) $task->subtasks->count(),
                'subtasks_done' => (int) $task->subtasks->where('is_completed', true)->count(),
            ];
        }

        return $columns;
    }
}
