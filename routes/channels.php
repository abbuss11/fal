<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('project.{projectId}', function (User $user, int $projectId): bool {
    $project = Project::query()->find($projectId);

    if (! $project) {
        return false;
    }

    return $user->canAccessProject($project);
});

Broadcast::channel('admin.tasks', function (User $user): bool {
    return $user->isAdmin();
});

Broadcast::channel('dashboard.{userId}', function (User $user, int $userId): bool {
    return $user->id === $userId;
});
