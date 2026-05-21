<?php

namespace App\Observers;

use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\TaskCommentAddedNotification;
use App\Notifications\UserMentionedNotification;
use App\Services\MentionResolverService;
use App\Support\Notifications\SendsNotificationsSafely;
use App\Support\Realtime\DashboardBroadcaster;
use App\Support\Realtime\WorkspaceBroadcaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TaskCommentObserver
{
    use SendsNotificationsSafely;

    /**
     * Handle the TaskComment "created" event.
     */
    public function created(TaskComment $taskComment): void
    {
        $taskComment->loadMissing(['task.project.owner', 'task.project.members']);
        $project = $taskComment->task?->project;

        $taskComment->task?->activityLogs()->create([
            'user_id' => $taskComment->user_id,
            'action' => 'task_comment_added',
            'meta' => [
                'comment_excerpt' => Str::limit($taskComment->body, 120),
            ],
        ]);

        foreach ($this->recipients($taskComment) as $recipient) {
            $this->notifySafely($recipient, new TaskCommentAddedNotification($taskComment), [
                'context' => 'task_comment_added',
                'task_id' => $taskComment->task_id,
                'comment_id' => $taskComment->id,
            ]);
        }

        if ($project) {
            /** @var MentionResolverService $resolver */
            $resolver = app(MentionResolverService::class);
            $mentionedUsers = $resolver->resolveUsers(
                text: $taskComment->body,
                project: $project,
                authorId: $taskComment->user_id ? (int) $taskComment->user_id : null,
            );

            foreach ($mentionedUsers as $mentionedUser) {
                $taskComment->mentions()->firstOrCreate([
                    'user_id' => $mentionedUser->id,
                ]);

                $this->notifySafely($mentionedUser, new UserMentionedNotification(
                    project: $project,
                    contextLabel: 'un commentaire de tache',
                    excerpt: Str::limit($taskComment->body, 180),
                    mentionedBy: $taskComment->user,
                ), [
                    'context' => 'task_comment_mention',
                    'task_id' => $taskComment->task_id,
                    'comment_id' => $taskComment->id,
                ]);
            }
        }

        WorkspaceBroadcaster::forProject($taskComment->task?->project_id, 'task_comment_added', [
            'task_id' => $taskComment->task_id,
            'comment_id' => $taskComment->id,
        ]);
        DashboardBroadcaster::forProject(
            $taskComment->task?->project_id,
            [$taskComment->user_id, $taskComment->task?->assigned_to],
            'task_comment_added',
            [
                'task_id' => $taskComment->task_id,
                'comment_id' => $taskComment->id,
            ],
        );
    }

    /**
     * Handle the TaskComment "updated" event.
     */
    public function updated(TaskComment $taskComment): void
    {
        //
    }

    /**
     * Handle the TaskComment "deleted" event.
     */
    public function deleted(TaskComment $taskComment): void
    {
        //
    }

    /**
     * Handle the TaskComment "restored" event.
     */
    public function restored(TaskComment $taskComment): void
    {
        //
    }

    /**
     * Handle the TaskComment "force deleted" event.
     */
    public function forceDeleted(TaskComment $taskComment): void
    {
        //
    }

    /**
     * @return Collection<int, User>
     */
    private function recipients(TaskComment $taskComment): Collection
    {
        $task = $taskComment->task;

        if (! $task) {
            return collect();
        }

        $users = collect([
            $task->assignee,
            $task->project?->owner,
            ...($task->project?->members?->where('pivot.is_active', true)->all() ?? []),
        ])
            ->filter(fn (mixed $user): bool => $user instanceof User)
            ->unique('id');

        if (! $taskComment->user_id) {
            return $users->values();
        }

        return $users
            ->reject(fn (User $user): bool => $user->id === $taskComment->user_id)
            ->values();
    }
}
