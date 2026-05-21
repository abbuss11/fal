<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectUpdatedNotification;
use App\Support\Notifications\SendsNotificationsSafely;
use App\Support\Realtime\DashboardBroadcaster;
use App\Support\Realtime\WorkspaceBroadcaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ProjectObserver
{
    use SendsNotificationsSafely;

    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        $actor = Auth::user();

        foreach ($this->recipients($project, $actor instanceof User ? $actor : null) as $recipient) {
            $this->notifySafely($recipient, new ProjectUpdatedNotification(
                project: $project,
                subjectLine: 'Nouveau projet cree',
                details: 'Un nouveau projet a ete cree et vous etes implique dans son execution.',
                updatedBy: $actor instanceof User ? $actor : null,
            ), [
                'context' => 'project_created',
                'project_id' => $project->id,
            ]);
        }

        WorkspaceBroadcaster::forProject($project, 'project_created');
        DashboardBroadcaster::forProject($project, [], 'project_created');
    }

    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project): void
    {
        $changes = $this->extractChanges($project);

        if ($changes === []) {
            return;
        }

        $actor = Auth::user();
        $details = 'Changements projet: '.implode(', ', $changes);

        foreach ($this->recipients($project, $actor instanceof User ? $actor : null) as $recipient) {
            $this->notifySafely($recipient, new ProjectUpdatedNotification(
                project: $project,
                subjectLine: 'Mise a jour du projet',
                details: $details,
                updatedBy: $actor instanceof User ? $actor : null,
            ), [
                'context' => 'project_updated',
                'project_id' => $project->id,
            ]);
        }

        WorkspaceBroadcaster::forProject($project, 'project_updated');
        DashboardBroadcaster::forProject($project, [], 'project_updated');
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        //
    }

    /**
     * Handle the Project "restored" event.
     */
    public function restored(Project $project): void
    {
        //
    }

    /**
     * Handle the Project "force deleted" event.
     */
    public function forceDeleted(Project $project): void
    {
        //
    }

    /**
     * @return Collection<int, User>
     */
    private function recipients(Project $project, ?User $actor): Collection
    {
        $project->loadMissing(['owner', 'members']);

        $users = collect([
            $project->owner,
            ...$project->members
                ->where('pivot.is_active', true)
                ->all(),
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

    /**
     * @return array<int, string>
     */
    private function extractChanges(Project $project): array
    {
        $fields = ['name', 'description', 'status', 'priority', 'start_date', 'due_date', 'budget', 'owner_id'];
        $changes = [];

        foreach ($fields as $field) {
            if (! $project->wasChanged($field)) {
                continue;
            }

            $changes[] = "{$field}: {$this->stringify($project->getOriginal($field))} -> {$this->stringify($project->{$field})}";
        }

        return $changes;
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
