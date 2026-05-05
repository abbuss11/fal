<?php

namespace App\Support\Realtime;

use App\Events\ProjectWorkspaceUpdated;
use App\Models\Project;

class WorkspaceBroadcaster
{
    public static function forProject(
        Project|int|null $project,
        string $reason = 'workspace_updated',
        array $meta = [],
    ): void {
        $projectId = match (true) {
            $project instanceof Project => $project->id,
            is_int($project) => $project,
            default => null,
        };

        if (! $projectId) {
            return;
        }

        event(new ProjectWorkspaceUpdated($projectId, $reason, $meta));
    }

    /**
     * @param iterable<int, int> $projectIds
     */
    public static function forProjects(
        iterable $projectIds,
        string $reason = 'workspace_updated',
        array $meta = [],
    ): void {
        $seen = [];

        foreach ($projectIds as $projectId) {
            $projectId = (int) $projectId;

            if ($projectId <= 0 || isset($seen[$projectId])) {
                continue;
            }

            $seen[$projectId] = true;
            event(new ProjectWorkspaceUpdated($projectId, $reason, $meta));
        }
    }
}
