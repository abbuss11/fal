<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Support\Realtime\DashboardBroadcaster;
use App\Support\Realtime\WorkspaceBroadcaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubtaskController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($task->project && $user->canAccessProject($task->project), 403);
        abort_unless($user->hasPermission('tasks.subtasks.manage'), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $position = (int) $task->subtasks()->max('position') + 1;

        $subtask = $task->subtasks()->create([
            'title' => $validated['title'],
            'position' => max(1, $position),
        ]);

        $task->activityLogs()->create([
            'user_id' => $user->id,
            'action' => 'task_subtask_created',
            'meta' => [
                'subtask_id' => $subtask->id,
                'title' => $subtask->title,
            ],
        ]);

        WorkspaceBroadcaster::forProject($task->project_id, 'task_subtask_created', [
            'task_id' => $task->id,
            'subtask_id' => $subtask->id,
        ]);
        DashboardBroadcaster::forProject($task->project_id, [$task->assigned_to], 'task_subtask_created', [
            'task_id' => $task->id,
            'subtask_id' => $subtask->id,
        ]);

        return back()->with('status', 'Sous-tache ajoutee.');
    }

    public function update(Request $request, Task $task, TaskSubtask $subtask): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($task->project && $user->canAccessProject($task->project), 403);
        abort_unless($user->hasPermission('tasks.subtasks.manage'), 403);
        abort_unless($subtask->task_id === $task->id, 404);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'is_completed' => ['nullable', 'boolean'],
        ]);

        $payload = [];
        if (array_key_exists('title', $validated) && $validated['title'] !== null) {
            $payload['title'] = $validated['title'];
        }

        if (array_key_exists('is_completed', $validated)) {
            $isCompleted = (bool) $validated['is_completed'];
            $payload['is_completed'] = $isCompleted;
            $payload['completed_by'] = $isCompleted ? $user->id : null;
            $payload['completed_at'] = $isCompleted ? now() : null;
        }

        if ($payload !== []) {
            $subtask->update($payload);
        }

        $task->activityLogs()->create([
            'user_id' => $user->id,
            'action' => 'task_subtask_updated',
            'meta' => [
                'subtask_id' => $subtask->id,
                'is_completed' => $subtask->is_completed,
            ],
        ]);

        WorkspaceBroadcaster::forProject($task->project_id, 'task_subtask_updated', [
            'task_id' => $task->id,
            'subtask_id' => $subtask->id,
        ]);
        DashboardBroadcaster::forProject($task->project_id, [$task->assigned_to], 'task_subtask_updated', [
            'task_id' => $task->id,
            'subtask_id' => $subtask->id,
        ]);

        return back()->with('status', 'Sous-tache mise a jour.');
    }
}

