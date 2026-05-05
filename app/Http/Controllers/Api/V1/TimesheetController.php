<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission('timesheets.read'), 403);

        $entries = $user->timesheets()
            ->with(['project:id,name', 'task:id,title'])
            ->latest('work_date')
            ->paginate(20);

        return response()->json($entries);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission('timesheets.create'), 403);

        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'task_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'work_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'note' => ['nullable', 'string', 'max:1500'],
        ]);

        $project = $user->visibleProjectsQuery()
            ->where('id', $validated['project_id'])
            ->firstOrFail();

        $taskId = (int) ($validated['task_id'] ?? 0);
        if ($taskId > 0) {
            $belongs = $project->tasks()->where('id', $taskId)->exists();
            abort_unless($belongs, 422, 'La tache ne correspond pas au projet.');
        }

        $timesheet = Timesheet::query()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'task_id' => $taskId > 0 ? $taskId : null,
            'work_date' => $validated['work_date'],
            'hours' => $validated['hours'],
            'note' => $validated['note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Temps enregistre.',
            'entry' => $timesheet->load(['project:id,name', 'task:id,title']),
        ], 201);
    }

    public function update(Request $request, Timesheet $timesheet): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $canEdit = $timesheet->user_id === $user->id
            || $user->isAdmin()
            || $user->hasPermission('timesheets.update');
        abort_unless($canEdit, 403);

        $validated = $request->validate([
            'work_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'note' => ['nullable', 'string', 'max:1500'],
        ]);

        $timesheet->update($validated);

        return response()->json([
            'message' => 'Temps mis a jour.',
            'entry' => $timesheet->fresh(['project:id,name', 'task:id,title']),
        ]);
    }

    public function destroy(Request $request, Timesheet $timesheet): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $canDelete = $timesheet->user_id === $user->id
            || $user->isAdmin()
            || $user->hasPermission('timesheets.update');
        abort_unless($canDelete, 403);

        $timesheet->delete();

        return response()->json([
            'message' => 'Temps supprime.',
        ]);
    }
}
