<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class KanbanBoard extends Page
{
    private const REVIEW_LANE = 'review';

    protected static string $resource = TaskResource::class;

    protected static string $view = 'filament.resources.task-resource.pages.kanban-board';

    protected static ?string $title = 'Kanban des taches';

    public $projectId = null;

    public string $boardMode = 'kanban';

    /**
     * @var array<int, string>
     */
    protected array $statusOrder = [
        Task::STATUS_TODO,
        Task::STATUS_DOING,
        self::REVIEW_LANE,
        Task::STATUS_DONE,
    ];

    /**
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            'echo-private:admin.tasks,ProjectWorkspaceUpdated' => 'refreshKanban',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getStatusLabelsProperty(): array
    {
        if ($this->boardMode === 'scrum') {
            return [
                Task::STATUS_TODO => 'Backlog',
                Task::STATUS_DOING => 'Sprint en cours',
                self::REVIEW_LANE => 'En cours de revue',
                Task::STATUS_DONE => 'Sprint termine',
            ];
        }

        return Task::statusOptions() + [
            self::REVIEW_LANE => 'En cours de revue',
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getColumnsProperty(): array
    {
        $columns = [
            Task::STATUS_TODO => [],
            Task::STATUS_DOING => [],
            self::REVIEW_LANE => [],
            Task::STATUS_DONE => [],
        ];

        $tasks = Task::query()
            ->with(['project', 'assignee'])
            ->when($this->projectId, fn ($query) => $query->where('project_id', $this->projectId))
            ->orderBy('status')
            ->orderBy('position')
            ->orderBy('due_date')
            ->get();

        foreach ($tasks as $task) {
            $status = $this->resolveLaneFromTask($task);

            $columns[$status][] = [
                'id' => $task->id,
                'title' => $task->title,
                'project' => $task->project?->name,
                'assignee' => $task->assignee?->name,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->format('d/m/Y H:i'),
                'status' => $status,
                'position' => (int) $task->position,
            ]; 
        }

        return $columns;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjectsProperty(): Collection
    {
        return Project::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function shiftTask(int $taskId, string $direction): void
    {
        $task = Task::query()->find($taskId);

        if (! $task) {
            return;
        }

        $currentLane = $this->resolveLaneFromTask($task);
        $currentIndex = array_search($currentLane, $this->statusOrder, true);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'left'
            ? max(0, $currentIndex - 1)
            : min(count($this->statusOrder) - 1, $currentIndex + 1);

        if ($targetIndex === $currentIndex) {
            return;
        }

        $this->moveTask($taskId, $this->statusOrder[$targetIndex]);
    }

    public function moveTask(int $taskId, string $targetLane, ?int $position = null): void
    {
        $task = Task::query()->find($taskId);

        if (! $task) {
            return;
        }

        [$targetStatus, $targetInReview, $normalizedTargetLane] = $this->resolveTargetState($targetLane);
        $currentStatus = $this->normalizeStatus((string) $task->status);
        $currentLane = $this->resolveLaneFromTask($task);
        $currentInReview = (bool) $task->is_in_review;
        $currentPosition = max((int) $task->position, 1);

        DB::transaction(function () use (
            $task,
            $targetStatus,
            $targetInReview,
            $normalizedTargetLane,
            $currentStatus,
            $currentLane,
            $currentInReview,
            $currentPosition,
            $position
        ): void {
            $requestedPosition = $position === null ? null : max((int) $position, 1);

            if ($normalizedTargetLane === $currentLane) {
                $maxPosition = $this->laneScopedQuery($task->project_id, $targetStatus, $targetInReview)
                    ->where('id', '!=', $task->id)
                    ->count() + 1;

                $targetPosition = $requestedPosition === null
                    ? $currentPosition
                    : min($requestedPosition, $maxPosition);

                if ($targetPosition !== $currentPosition) {
                    if ($targetPosition < $currentPosition) {
                        $this->laneScopedQuery($task->project_id, $targetStatus, $targetInReview)
                            ->where('id', '!=', $task->id)
                            ->whereBetween('position', [$targetPosition, $currentPosition - 1])
                            ->increment('position');
                    } else {
                        $this->laneScopedQuery($task->project_id, $targetStatus, $targetInReview)
                            ->where('id', '!=', $task->id)
                            ->whereBetween('position', [$currentPosition + 1, $targetPosition])
                            ->decrement('position');
                    }
                }

                $task->forceFill([
                    'position' => $targetPosition,
                ])->save();

                return;
            }

            $this->laneScopedQuery($task->project_id, $currentStatus, $currentInReview)
                ->where('position', '>', $currentPosition)
                ->decrement('position');

            $maxTargetPosition = $this->laneScopedQuery($task->project_id, $targetStatus, $targetInReview)
                ->count() + 1;

            $targetPosition = $requestedPosition === null
                ? $maxTargetPosition
                : min($requestedPosition, $maxTargetPosition);

            $this->laneScopedQuery($task->project_id, $targetStatus, $targetInReview)
                ->where('position', '>=', $targetPosition)
                ->increment('position');

            $task->forceFill([
                'status' => $targetStatus,
                'is_in_review' => $targetInReview,
                'position' => $targetPosition,
            ])->save();
        });
    }

    public function refreshKanban(): void {}

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retour')
                ->label('Retour liste')
                ->icon('heroicon-o-list-bullet')
                ->url(TaskResource::getUrl('index')),
            Action::make('create')
                ->label('Nouvelle tache')
                ->icon('heroicon-o-plus')
                ->url(TaskResource::getUrl('create')),
            Action::make('calendar')
                ->label('Vue calendrier')
                ->icon('heroicon-o-calendar')
                ->url(TaskResource::getUrl('calendar')),
        ];
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            Task::STATUS_TODO, Task::STATUS_DOING, Task::STATUS_DONE => $status,
            'in_progress', 'blocked' => Task::STATUS_DOING,
            default => Task::STATUS_TODO,
        };
    }

    private function resolveLaneFromTask(Task $task): string
    {
        return $task->normalized_status === Task::STATUS_DOING && (bool) $task->is_in_review
            ? self::REVIEW_LANE
            : $task->normalized_status;
    }

    /**
     * @return array{0:string,1:bool,2:string}
     */
    private function resolveTargetState(string $targetLane): array
    {
        $targetLane = strtolower(trim($targetLane));

        return match ($targetLane) {
            self::REVIEW_LANE => [Task::STATUS_DOING, true, self::REVIEW_LANE],
            Task::STATUS_DOING => [Task::STATUS_DOING, false, Task::STATUS_DOING],
            Task::STATUS_DONE => [Task::STATUS_DONE, false, Task::STATUS_DONE],
            default => [Task::STATUS_TODO, false, Task::STATUS_TODO],
        };
    }

    private function laneScopedQuery(int $projectId, string $status, bool $isInReview): \Illuminate\Database\Eloquent\Builder
    {
        $query = Task::query()
            ->where('project_id', $projectId)
            ->where('status', $status);

        if ($status === Task::STATUS_DOING) {
            $query->where('is_in_review', $isInReview);
        }

        return $query;
    }
}
