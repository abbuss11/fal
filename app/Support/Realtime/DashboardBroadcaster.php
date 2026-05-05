<?php

namespace App\Support\Realtime;

use App\Events\UserDashboardUpdated;
use App\Models\Project;

class DashboardBroadcaster
{
    public static function forProject(
        Project|int|null $project,
        array $extraUserIds = [],
        string $reason = 'dashboard_updated',
        array $meta = [],
    ): void {
        $projectModel = match (true) {
            $project instanceof Project => $project,
            is_int($project) => Project::query()->find($project),
            default => null,
        };

        if (! $projectModel) {
            self::forUsers($extraUserIds, $reason, $meta);

            return;
        }

        $userIds = collect($extraUserIds)
            ->filter(fn (mixed $id): bool => (int) $id > 0)
            ->map(fn (mixed $id): int => (int) $id);

        if ($projectModel->owner_id) {
            $userIds->push((int) $projectModel->owner_id);
        }

        $memberIds = $projectModel->members()
            ->wherePivot('is_active', true)
            ->pluck('users.id')
            ->map(fn (mixed $id): int => (int) $id);

        $userIds = $userIds
            ->merge($memberIds)
            ->unique()
            ->values()
            ->all();

        self::forUsers($userIds, $reason, $meta);
    }

    /**
     * @param iterable<int, int|string|null> $userIds
     */
    public static function forUsers(
        iterable $userIds,
        string $reason = 'dashboard_updated',
        array $meta = [],
    ): void {
        $seen = [];

        foreach ($userIds as $userId) {
            $userId = (int) $userId;

            if ($userId <= 0 || isset($seen[$userId])) {
                continue;
            }

            $seen[$userId] = true;
            event(new UserDashboardUpdated($userId, $reason, $meta));
        }
    }
}
