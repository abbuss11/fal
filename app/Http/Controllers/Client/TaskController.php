<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskTag;
use App\Models\User;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'canCreateTask' => $user->hasPermission('tasks.create'),
            'canUpdateTask' => $user->hasPermission('tasks.update'),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->hasPermission('tasks.create'), 403);

        $projects = $user->visibleProjectsQuery()
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($projects->isEmpty()) {
            return redirect()
                ->route('client.tasks.index')
                ->with('status', 'Aucun projet accessible pour creer une tache.');
        }

        $selectedProjectId = $request->integer('project_id');
        if (! $projects->contains('id', $selectedProjectId)) {
            $selectedProjectId = (int) $projects->first()->id;
        }

        [$assignees, $dependencyCandidates] = $this->taskFormContext($user, $selectedProjectId);

        return view('client.tasks.create', [
            'task' => new Task(),
            'projects' => $projects,
            'assignees' => $assignees,
            'dependencyCandidates' => $dependencyCandidates,
            'tags' => TaskTag::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => Task::statusOptions(),
            'priorityOptions' => Task::priorityOptions(),
            'selectedProjectId' => $selectedProjectId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->hasPermission('tasks.create'), 403);

        $validated = $this->validatedTaskPayload($request);
        $project = Project::query()->findOrFail((int) $validated['project_id']);
        abort_unless($user->canAccessProject($project), 403);

        $assigneeId = $this->resolvedAssigneeId($validated, $project);
        $isInReview = (bool) ($validated['is_in_review'] ?? false) && $validated['status'] === Task::STATUS_DOING;
        $position = $this->nextPosition($project->id, (string) $validated['status'], $isInReview);

        $task = Task::query()->create([
            'project_id' => $project->id,
            'assigned_to' => $assigneeId,
            'title' => trim((string) $validated['title']),
            'description' => $validated['description'] ?? null,
            'status' => (string) $validated['status'],
            'is_in_review' => $isInReview,
            'priority' => (string) $validated['priority'],
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'position' => $position,
        ]);

        $task->tags()->sync($validated['tag_ids'] ?? []);
        $task->dependencies()->sync(
            $this->validatedDependencyIds(
                (array) ($validated['dependency_ids'] ?? []),
                $project->id,
                null,
            )
        );

        return redirect()
            ->to(route('client.projects.show', $project).'#board')
            ->with('status', 'Tache creee avec succes.');
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

    public function edit(Request $request, Task $task): View
    {
        /** @var User $user */
        $user = $request->user();

        $task->loadMissing(['project', 'tags', 'dependencies']);
        abort_unless($task->project && $user->canAccessProject($task->project), 403);

        $projects = $user->visibleProjectsQuery()
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedProjectId = (int) old('project_id', $task->project_id);
        if (! $projects->contains('id', $selectedProjectId)) {
            $selectedProjectId = $task->project_id;
        }

        [$assignees, $dependencyCandidates] = $this->taskFormContext($user, $selectedProjectId, $task->id);

        return view('client.tasks.edit', [
            'task' => $task,
            'projects' => $projects,
            'assignees' => $assignees,
            'dependencyCandidates' => $dependencyCandidates,
            'tags' => TaskTag::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => Task::statusOptions(),
            'priorityOptions' => Task::priorityOptions(),
            'selectedProjectId' => $selectedProjectId,
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $task->loadMissing('project');
        abort_unless($task->project && $user->canAccessProject($task->project), 403);

        $validated = $this->validatedTaskPayload($request);
        $targetProject = Project::query()->findOrFail((int) $validated['project_id']);
        abort_unless($user->canAccessProject($targetProject), 403);

        $assigneeId = $this->resolvedAssigneeId($validated, $targetProject);
        $isInReview = (bool) ($validated['is_in_review'] ?? false) && $validated['status'] === Task::STATUS_DOING;
        $shouldReposition = $task->project_id !== $targetProject->id
            || $task->status !== $validated['status']
            || (bool) $task->is_in_review !== $isInReview;

        $payload = [
            'project_id' => $targetProject->id,
            'assigned_to' => $assigneeId,
            'title' => trim((string) $validated['title']),
            'description' => $validated['description'] ?? null,
            'status' => (string) $validated['status'],
            'is_in_review' => $isInReview,
            'priority' => (string) $validated['priority'],
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
        ];

        if ($shouldReposition) {
            $payload['position'] = $this->nextPosition(
                $targetProject->id,
                (string) $validated['status'],
                $isInReview,
                $task->id,
            );
        }

        $task->update($payload);
        $task->tags()->sync($validated['tag_ids'] ?? []);
        $task->dependencies()->sync(
            $this->validatedDependencyIds(
                (array) ($validated['dependency_ids'] ?? []),
                $targetProject->id,
                $task->id,
            )
        );

        return redirect()
            ->to(route('client.projects.show', $targetProject).'#board')
            ->with('status', 'Tache mise a jour.');
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $task->loadMissing('project');
        abort_unless($task->project && $user->canAccessProject($task->project), 403);

        $project = $task->project;
        $taskTitle = $task->title;
        $task->delete();

        return redirect()
            ->to(route('client.projects.show', $project).'#board')
            ->with('status', "Tache \"{$taskTitle}\" supprimee.");
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

    /**
     * @return array<string, mixed>
     */
    private function validatedTaskPayload(Request $request): array
    {
        return $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Task::statusOptions()))],
            'is_in_review' => ['nullable', 'boolean'],
            'priority' => ['required', Rule::in(array_keys(Task::priorityOptions()))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'estimated_hours' => ['nullable', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:task_tags,id'],
            'dependency_ids' => ['nullable', 'array'],
            'dependency_ids.*' => ['integer', 'exists:tasks,id'],
        ]);
    }

    /**
     * @return array{0:Collection<int,User>,1:Collection<int,Task>}
     */
    private function taskFormContext(User $user, int $projectId, ?int $exceptTaskId = null): array
    {
        $project = Project::query()
            ->with([
                'owner:id,name',
                'members' => fn ($query) => $query->select('users.id', 'users.name')->wherePivot('is_active', true),
            ])
            ->find($projectId);

        if (! $project || ! $user->canAccessProject($project)) {
            return [collect(), collect()];
        }

        $assignees = collect([$project->owner])
            ->merge($project->members)
            ->filter(fn (mixed $member): bool => $member instanceof User)
            ->unique('id')
            ->sortBy('name')
            ->values();

        $dependencyCandidates = $project->tasks()
            ->when($exceptTaskId !== null, fn ($query) => $query->where('id', '!=', $exceptTaskId))
            ->orderBy('title')
            ->get(['id', 'title']);

        return [$assignees, $dependencyCandidates];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolvedAssigneeId(array $validated, Project $project): ?int
    {
        if (! isset($validated['assigned_to']) || $validated['assigned_to'] === null || $validated['assigned_to'] === '') {
            return null;
        }

        $assigneeId = (int) $validated['assigned_to'];
        $isAssignable = $project->owner_id === $assigneeId
            || $project->members()
                ->where('users.id', $assigneeId)
                ->wherePivot('is_active', true)
                ->exists();

        if (! $isAssignable) {
            throw ValidationException::withMessages([
                'assigned_to' => 'L assigne choisi ne fait pas partie du projet.',
            ]);
        }

        return $assigneeId;
    }

    /**
     * @param  array<int, mixed>  $dependencyIds
     * @return array<int, int>
     */
    private function validatedDependencyIds(array $dependencyIds, int $projectId, ?int $exceptTaskId): array
    {
        $ids = collect($dependencyIds)
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $query = Task::query()
            ->where('project_id', $projectId)
            ->whereIn('id', $ids->all());

        if ($exceptTaskId !== null) {
            $query->where('id', '!=', $exceptTaskId);
        }

        return $query->pluck('id')->map(fn (int $id): int => $id)->values()->all();
    }

    private function nextPosition(int $projectId, string $status, bool $isInReview, ?int $exceptTaskId = null): int
    {
        $query = Task::query()
            ->where('project_id', $projectId)
            ->where('status', $status);

        if ($status === Task::STATUS_DOING) {
            $query->where('is_in_review', $isInReview);
        }

        if ($exceptTaskId !== null) {
            $query->where('id', '!=', $exceptTaskId);
        }

        return ((int) $query->max('position')) + 1;
    }
}
