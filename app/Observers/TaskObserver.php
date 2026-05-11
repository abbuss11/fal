<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskStatusUpdatedNotification;
use App\Notifications\TaskUpdatedNotification;
use App\Support\Realtime\DashboardBroadcaster;
use App\Support\Realtime\WorkspaceBroadcaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TaskObserver
{
    /**
     * Handle the Task "creating" event.
     */
    public function creating(Task $task): void
    {
        $this->normalizeStatus($task);
        $this->syncCompletedAt($task);
        $this->syncPosition($task);
    }

    /**
     * Handle the Task "updating" event.
     */
    public function updating(Task $task): void
    {
        $this->normalizeStatus($task);
        $this->syncCompletedAt($task);
    }

    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        $this->logActivity($task, 'task_created', [
            'title' => $task->title,
            'status' => $task->status,
        ]);

        if ($task->assigned_to) {
            $this->sendAssignmentNotification($task, null);
        }

        WorkspaceBroadcaster::forProject($task->project_id, 'task_created', [
            'task_id' => $task->id,
        ]);
        DashboardBroadcaster::forProject($task->project_id, [$task->assigned_to], 'task_created', [
            'task_id' => $task->id,
        ]);
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        $actor = Auth::user();

        if ($task->wasChanged('assigned_to')) {
            $this->logActivity($task, 'task_assigned', [
                'assigned_to' => $task->assigned_to,
                'from' => $task->getOriginal('assigned_to'),
                'to' => $task->assigned_to,
            ]);

            $this->sendAssignmentNotification($task, $actor instanceof User ? $actor : null);
        }

        if ($task->wasChanged('status')) {
            $oldStatus = (string) $task->getOriginal('status');
            $newStatus = (string) $task->status;

            $this->logActivity($task, 'task_status_changed', [
                'from' => $oldStatus,
                'to' => $newStatus,
            ]);

            $this->sendStatusNotification(
                $task,
                $oldStatus,
                $newStatus,
                $actor instanceof User ? $actor : null,
            );
        }

        $this->handleGenericTaskUpdate($task, $actor instanceof User ? $actor : null);

        WorkspaceBroadcaster::forProject($task->project_id, 'task_updated', [
            'task_id' => $task->id,
        ]);
        DashboardBroadcaster::forProject(
            $task->project_id,
            [$task->assigned_to, $task->getOriginal('assigned_to')],
            'task_updated',
            ['task_id' => $task->id],
        );
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        WorkspaceBroadcaster::forProject($task->project_id, 'task_deleted', [
            'task_id' => $task->id,
        ]);
        DashboardBroadcaster::forProject($task->project_id, [$task->assigned_to], 'task_deleted', [
            'task_id' => $task->id,
        ]);
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }

    private function normalizeStatus(Task $task): void
    {
        if ($task->status === 'in_progress' || $task->status === 'blocked') {
            $task->status = Task::STATUS_DOING;
        }

        if (
            $task->status === Task::STATUS_DONE
            && $task->exists
            && $task->dependencies()->where('status', '!=', Task::STATUS_DONE)->exists()
        ) {
            $task->status = Task::STATUS_DOING;
            $task->is_in_review = true;
        }

        if ($task->status !== Task::STATUS_DOING) {
            $task->is_in_review = false;
        }
    }

    private function syncCompletedAt(Task $task): void
    {
        if ($task->status === Task::STATUS_DONE && ! $task->completed_at) {
            $task->completed_at = now();
        }

        if (
            $task->isDirty('status')
            && $task->status !== Task::STATUS_DONE
            && $task->getOriginal('status') === Task::STATUS_DONE
        ) {
            $task->completed_at = null;
        }
    }

    private function syncPosition(Task $task): void
    {
        if ($task->position > 0) {
            return;
        }

        $task->position = Task::query()
            ->where('project_id', $task->project_id)
            ->where('status', $task->status)
            ->max('position') + 1;
    }

    private function logActivity(Task $task, string $action, array $meta = []): void
    {
        $actor = Auth::user();

        $task->activityLogs()->create([
            'user_id' => $actor instanceof User ? $actor->id : null,
            'action' => $action,
            'meta' => $meta,
        ]);
    }

    private function sendAssignmentNotification(Task $task, ?User $actor): void
    {
        if (! $task->assigned_to) {
            return;
        }

        $assignee = $task->assignee()->first();

        if (! $assignee instanceof User) {
            return;
        }

        if ($actor && $assignee->is($actor)) {
            return;
        }

        $assignee->notify(new TaskAssignedNotification($task, $actor));
    }

    private function sendStatusNotification(
        Task $task,
        string $oldStatus,
        string $newStatus,
        ?User $actor,
    ): void {
        $recipients = $this->statusRecipients($task, $actor);

        foreach ($recipients as $recipient) {
            $recipient->notify(new TaskStatusUpdatedNotification($task, $oldStatus, $newStatus, $actor));
        }
    }

    private function handleGenericTaskUpdate(Task $task, ?User $actor): void
    {
        $trackedFields = ['title', 'description', 'priority', 'due_date', 'estimated_hours', 'project_id'];
        $changes = [];

        foreach ($trackedFields as $field) {
            if (! $task->wasChanged($field)) {
                continue;
            }

            $changes[$field] = [
                'from' => $this->stringify($task->getOriginal($field)),
                'to' => $this->stringify($task->{$field}),
            ];
        }

        if ($changes === []) {
            return;
        }

        $this->logActivity($task, 'task_updated', [
            'changes' => $changes,
        ]);

        foreach ($this->statusRecipients($task, $actor) as $recipient) {
            $recipient->notify(new TaskUpdatedNotification($task, $changes, $actor));
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function statusRecipients(Task $task, ?User $actor): Collection
    {
        $task->loadMissing(['assignee', 'project.owner', 'project.members']);

        $users = collect([
            $task->assignee,
            $task->project?->owner,
            ...($task->project?->members?->where('pivot.is_active', true)->all() ?? []),
        ])
            ->filter(fn (mixed $user): bool => $user instanceof User)
            ->unique('id');

        if (! $actor) {
            return $users->values();
        }

        return $users
            ->reject(fn (User $user): bool => $user->is($actor))
            ->values();
    }

    private function stringify(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '-';
    }
}
