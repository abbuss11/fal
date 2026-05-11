<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskTag;
use App\Models\User;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $status = $request->string('status')->toString();
        $projectId = $request->integer('project_id');
        $tagId = $request->integer('tag_id');

        $tasksQuery = $user->visibleTasksQuery()
            ->with(['project', 'assignee'])
            ->withCount([
                'subtasks',
                'subtasks as subtasks_done_count' => fn ($query) => $query->where('is_completed', true),
            ])
            ->orderByRaw("CASE status WHEN 'todo' THEN 1 WHEN 'doing' THEN 2 WHEN 'done' THEN 3 ELSE 4 END")
            ->orderBy('due_date');

        if ($status !== '' && array_key_exists($status, Task::statusOptions())) {
            $tasksQuery->where('status', $status);
        }

        if ($projectId > 0) {
            $tasksQuery->where('project_id', $projectId);
        }

        if ($tagId > 0) {
            $tasksQuery->whereHas('tags', fn ($query) => $query->where('task_tags.id', $tagId));
        }

        $tasks = $tasksQuery
            ->paginate(12)
            ->withQueryString();

        $projects = $user->visibleProjectsQuery()
            ->orderBy('name')
            ->get(['id', 'name']);
        $tags = TaskTag::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('client.tasks.index', [
            'tasks' => $tasks,
            'projects' => $projects,
            'tags' => $tags,
            'statuses' => Task::statusOptions(),
            'selectedStatus' => $status,
            'selectedProjectId' => $projectId > 0 ? $projectId : null,
            'selectedTagId' => $tagId > 0 ? $tagId : null,
        ]);
    }

    public function calendar(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $month = $request->string('month')->toString();
        try {
            $currentMonth = $month !== ''
                ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
                : now()->startOfMonth();
        } catch (InvalidFormatException) {
            $currentMonth = now()->startOfMonth();
        }

        $start = $currentMonth->copy()->startOfWeek();
        $end = $currentMonth->copy()->endOfMonth()->endOfWeek();

        $tasks = $user->visibleTasksQuery()
            ->with(['project', 'assignee'])
            ->whereBetween('due_date', [$start, $end])
            ->orderBy('due_date')
            ->get();

        $tasksByDate = $tasks->groupBy(fn (Task $task): string => $task->due_date?->format('Y-m-d') ?? 'no-date');

        $days = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');

            $days[] = [
                'date' => $cursor->copy(),
                'is_current_month' => $cursor->month === $currentMonth->month,
                'tasks' => $tasksByDate[$key] ?? collect(),
            ];

            $cursor->addDay();
        }

        return view('client.tasks.calendar', [
            'month' => $currentMonth,
            'days' => $days,
            'statuses' => Task::statusOptions(),
        ]);
    }

    public function storeComment(Request $request, Task $task): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($task->project && $user->canAccessProject($task->project), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
        ]);

        TaskComment::query()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'body' => $validated['body'],
        ]);

        return back()->with('status', 'Commentaire ajoute.');
    }
}
