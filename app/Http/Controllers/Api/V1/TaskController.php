<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $status = $request->string('status')->toString();
        $projectId = $request->integer('project_id');
        $assignedToMe = $request->boolean('assigned_to_me', true);

        $query = $user->visibleTasksQuery()
            ->with([
                'project:id,name',
                'assignee:id,name,email',
                'subtasks:id,task_id,is_completed',
            ])
            ->orderBy('due_date');

        if ($status !== '' && array_key_exists($status, Task::statusOptions())) {
            $query->where('status', $status);
        }

        if ($projectId > 0) {
            $query->where('project_id', $projectId);
        }

        if ($assignedToMe) {
            $query->where('assigned_to', $user->id);
        }

        $tasks = $query->paginate(20);

        return response()->json($tasks);
    }

    public function quickUpdateStatus(Request $request, Task $task): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($task->project && $user->canAccessProject($task->project), 403);
        abort_unless($user->hasPermission('tasks.move'), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Task::statusOptions()))],
        ]);

        $task->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Statut mis a jour.',
            'task' => $task->fresh(['project:id,name', 'assignee:id,name,email']),
        ]);
    }
}

