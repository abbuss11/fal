<?php

namespace App\Http\Controllers\Client;

use App\Domain\Projects\Services\ProjectWorkspaceService;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Notifications\ProjectUpdatedNotification;
use App\Services\ProjectReportService;
use App\Support\Realtime\DashboardBroadcaster;
use App\Support\Realtime\WorkspaceBroadcaster;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    private const REVIEW_LANE = 'review';

    public function __construct(
        private readonly ProjectReportService $reportService,
        private readonly ProjectWorkspaceService $workspaceService,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $projects = $user->visibleProjectsQuery()
            ->with('owner')
            ->withCount([
                'tasks',
                'tasks as tasks_done_count' => fn ($query) => $query->where('status', Task::STATUS_DONE),
            ])
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('client.projects.index', [
            'projects' => $projects,
        ]);
    }

    public function create(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->hasPermission('projects.create'), 403);

        $clients = Client::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('client.projects.create', [
            'project' => new Project(),
            'clients' => $clients,
            'statusOptions' => Project::statusOptions(),
            'priorityOptions' => Project::priorityOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->hasPermission('projects.create'), 403);

        $payload = $this->validatedProjectPayload($request);
        $payload['owner_id'] = $user->id;

        $project = Project::query()->create($payload);
        $project->members()->syncWithoutDetaching([
            $user->id => [
                'role' => User::ROLE_PROJECT_MANAGER,
                'is_active' => true,
            ],
        ]);

        return redirect()
            ->route('client.projects.show', $project)
            ->with('status', 'Projet cree avec succes.');
    }

    public function show(Request $request, Project $project): View
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);

        $project->load([
            'owner',
            'members' => fn ($query) => $query->orderBy('name')->withPivot(['role', 'is_active']),
        ]);

        $tasks = $project->tasks()
            ->with(['assignee', 'subtasks'])
            ->orderBy('position')
            ->orderBy('due_date')
            ->get();

        $tasksByStatus = $tasks->groupBy(fn (Task $task): string => $task->board_lane);
        $tasksByDate = $tasks
            ->filter(fn (Task $task): bool => $task->due_date !== null)
            ->groupBy(fn (Task $task): string => $task->due_date->format('Y-m-d'));

        $overview = $this->reportService->build($project);
        $recentComments = $project->comments()
            ->with(['user', 'task'])
            ->latest()
            ->limit(12)
            ->get();
        $recentMessages = $project->messages()
            ->with('user')
            ->latest()
            ->limit(60)
            ->get()
            ->reverse()
            ->values();
        $projectFiles = $project->files()
            ->with(['uploader', 'task'])
            ->latest()
            ->limit(60)
            ->get();

        $availableMembers = User::query()
            ->where('is_active', true)
            ->whereNotIn('id', $project->members->pluck('id'))
            ->where('id', '!=', $project->owner_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $statusOrder = [
            Task::STATUS_TODO,
            Task::STATUS_DOING,
            self::REVIEW_LANE,
            Task::STATUS_DONE,
        ];

        $liveSnapshot = $this->workspaceService->buildSnapshot($project, $tasks, $overview, $recentComments);

        return view('client.projects.show', [
            'project' => $project,
            'overview' => $overview,
            'tasks' => $tasks,
            'tasksByStatus' => $tasksByStatus,
            'tasksByDate' => $tasksByDate,
            'recentComments' => $recentComments,
            'availableMembers' => $availableMembers,
            'statusOrder' => $statusOrder,
            'statusLabels' => Task::statusOptions(),
            'boardStatusLabels' => Task::statusOptions() + [
                self::REVIEW_LANE => 'En cours de revue',
            ],
            'priorityLabels' => Task::priorityOptions(),
            'roleLabels' => User::roleOptions(),
            'recentMessages' => $recentMessages,
            'projectFiles' => $projectFiles,
            'canManageProject' => $user->canManageProject($project),
            'snapshotVersion' => $this->workspaceService->snapshotVersion($project),
            'liveSnapshot' => $liveSnapshot,
        ]);
    }

    public function edit(Request $request, Project $project): View
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canManageProject($project), 403);

        $clients = Client::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('client.projects.edit', [
            'project' => $project,
            'clients' => $clients,
            'statusOptions' => Project::statusOptions(),
            'priorityOptions' => Project::priorityOptions(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canManageProject($project), 403);

        $project->update($this->validatedProjectPayload($request));

        return redirect()
            ->route('client.projects.show', $project)
            ->with('status', 'Projet mis a jour.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canManageProject($project), 403);

        $projectName = $project->name;
        $project->delete();

        return redirect()
            ->route('client.projects.index')
            ->with('status', "Projet \"{$projectName}\" supprime.");
    }

    public function moveTask(Request $request, Project $project, Task $task): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);
        abort_unless($task->project_id === $project->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Task::statusOptions()))],
            'lane' => ['nullable', Rule::in([Task::STATUS_TODO, Task::STATUS_DOING, self::REVIEW_LANE, Task::STATUS_DONE])],
            'position' => ['nullable', 'integer', 'min:1'],
        ]);

        $targetStatus = (string) $validated['status'];
        $targetLaneInput = isset($validated['lane']) ? (string) $validated['lane'] : null;
        $requestedPosition = array_key_exists('position', $validated)
            ? max((int) $validated['position'], 1)
            : null;
        [$targetStatus, $targetInReview, $targetLane] = $this->resolveTargetState($targetStatus, $targetLaneInput);

        $currentStatus = $task->normalized_status;
        $currentLane = $task->board_lane;
        $currentInReview = (bool) $task->is_in_review;
        $currentPosition = max((int) $task->position, 1);

        DB::transaction(function () use (
            $task,
            $project,
            $targetStatus,
            $targetInReview,
            $targetLane,
            $requestedPosition,
            $currentStatus,
            $currentLane,
            $currentInReview,
            $currentPosition
        ): void {
            if ($targetLane === $currentLane) {
                $maxPosition = $this->laneScopedQuery($project->id, $targetStatus, $targetInReview)
                    ->where('id', '!=', $task->id)
                    ->count() + 1;

                $targetPosition = $requestedPosition === null
                    ? $currentPosition
                    : min($requestedPosition, $maxPosition);

                if ($targetPosition !== $currentPosition) {
                    if ($targetPosition < $currentPosition) {
                        $this->laneScopedQuery($project->id, $targetStatus, $targetInReview)
                            ->where('id', '!=', $task->id)
                            ->whereBetween('position', [$targetPosition, $currentPosition - 1])
                            ->increment('position');
                    } else {
                        $this->laneScopedQuery($project->id, $targetStatus, $targetInReview)
                            ->where('id', '!=', $task->id)
                            ->whereBetween('position', [$currentPosition + 1, $targetPosition])
                            ->decrement('position');
                    }
                }

                $task->update([
                    'position' => $targetPosition,
                ]);

                return;
            }

            $this->laneScopedQuery($project->id, $currentStatus, $currentInReview)
                ->where('position', '>', $currentPosition)
                ->decrement('position');

            $maxTargetPosition = $this->laneScopedQuery($project->id, $targetStatus, $targetInReview)
                ->count() + 1;

            $targetPosition = $requestedPosition === null
                ? $maxTargetPosition
                : min($requestedPosition, $maxTargetPosition);

            $this->laneScopedQuery($project->id, $targetStatus, $targetInReview)
                ->where('position', '>=', $targetPosition)
                ->increment('position');

            $task->update([
                'status' => $targetStatus,
                'is_in_review' => $targetInReview,
                'position' => $targetPosition,
            ]);
        });

        return response()->json([
            'ok' => true,
            'task_id' => $task->id,
            'status' => $task->status,
            'lane' => $task->board_lane,
            'position' => $task->position,
            'snapshot_version' => $this->workspaceService->snapshotVersion($project),
        ]);
    }

    public function snapshot(Request $request, Project $project): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);

        return response()->json($this->workspaceService->buildSnapshot($project));
    }

    public function storeMember(Request $request, Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canManageProject($project), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', Rule::in([User::ROLE_PROJECT_MANAGER, User::ROLE_MANAGER, User::ROLE_MEMBER, User::ROLE_CLIENT])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $project->members()->syncWithoutDetaching([
            $validated['user_id'] => [
                'role' => $validated['role'],
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ],
        ]);

        $member = User::query()->find($validated['user_id']);
        if ($member) {
            $member->notify(new ProjectUpdatedNotification(
                project: $project,
                subjectLine: 'Vous avez ete ajoute au projet',
                details: 'Votre compte a ete ajoute a un projet collaboratif.',
                updatedBy: $user,
            ));
        }

        WorkspaceBroadcaster::forProject($project, 'project_member_added', [
            'member_id' => (int) $validated['user_id'],
        ]);
        DashboardBroadcaster::forProject($project, [$validated['user_id']], 'project_member_added', [
            'member_id' => (int) $validated['user_id'],
        ]);

        return back()->with('status', 'Membre ajoute au projet.');
    }

    public function updateMember(Request $request, Project $project, User $member): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canManageProject($project), 403);
        abort_unless($project->members()->where('users.id', $member->id)->exists(), 404);

        $validated = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_PROJECT_MANAGER, User::ROLE_MANAGER, User::ROLE_MEMBER, User::ROLE_CLIENT])],
            'is_active' => ['required', 'boolean'],
        ]);

        $project->members()->updateExistingPivot($member->id, [
            'role' => $validated['role'],
            'is_active' => (bool) $validated['is_active'],
        ]);

        $member->notify(new ProjectUpdatedNotification(
            project: $project,
            subjectLine: 'Mise a jour de votre role projet',
            details: 'Votre role ou votre statut actif sur le projet a ete modifie.',
            updatedBy: $user,
        ));

        WorkspaceBroadcaster::forProject($project, 'project_member_updated', [
            'member_id' => $member->id,
        ]);
        DashboardBroadcaster::forProject($project, [$member->id], 'project_member_updated', [
            'member_id' => $member->id,
        ]);

        return back()->with('status', 'Membre mis a jour.');
    }

    public function removeMember(Request $request, Project $project, User $member): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canManageProject($project), 403);

        $project->members()->detach($member->id);

        $member->notify(new ProjectUpdatedNotification(
            project: $project,
            subjectLine: 'Retrait du projet',
            details: 'Votre compte a ete retire de ce projet.',
            updatedBy: $user,
        ));

        WorkspaceBroadcaster::forProject($project, 'project_member_removed', [
            'member_id' => $member->id,
        ]);
        DashboardBroadcaster::forProject($project, [$member->id], 'project_member_removed', [
            'member_id' => $member->id,
        ]);

        return back()->with('status', 'Membre retire.');
    }

    public function archive(Request $request, Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canManageProject($project), 403);

        $project->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        WorkspaceBroadcaster::forProject($project, 'project_archived', [
            'project_id' => $project->id,
        ]);

        return back()->with('status', 'Projet archive.');
    }

    public function unarchive(Request $request, Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canManageProject($project), 403);

        $project->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        WorkspaceBroadcaster::forProject($project, 'project_unarchived', [
            'project_id' => $project->id,
        ]);

        return back()->with('status', 'Projet desarchive.');
    }

    public function duplicate(Request $request, Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canManageProject($project), 403);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'with_tasks' => ['nullable', 'boolean'],
            'mark_as_template' => ['nullable', 'boolean'],
        ]);

        $withTasks = (bool) ($validated['with_tasks'] ?? true);
        $name = isset($validated['name']) && trim((string) $validated['name']) !== ''
            ? trim((string) $validated['name'])
            : $project->name.' (copie)';

        $newProject = DB::transaction(function () use ($project, $user, $name, $withTasks, $validated): Project {
            $project->loadMissing([
                'members' => fn ($query) => $query->withPivot(['role', 'is_active']),
                'tasks.tags',
                'tasks.subtasks',
                'tasks.dependencies',
            ]);

            $clonedProject = $project->replicate([
                'completed_at',
                'archived_at',
            ]);
            $clonedProject->name = $name;
            $clonedProject->status = Project::STATUS_PLANNING;
            $clonedProject->is_archived = false;
            $clonedProject->archived_at = null;
            $clonedProject->is_template = (bool) ($validated['mark_as_template'] ?? false);
            $clonedProject->template_name = $clonedProject->is_template
                ? ($project->template_name ?: $project->name)
                : null;
            $clonedProject->completed_at = null;
            $clonedProject->owner_id = $user->id;
            $clonedProject->save();

            $members = $project->members
                ->mapWithKeys(fn (User $member): array => [
                    $member->id => [
                        'role' => (string) ($member->pivot->role ?? User::ROLE_MEMBER),
                        'is_active' => (bool) ($member->pivot->is_active ?? true),
                    ],
                ])
                ->all();

            $members[$user->id] = [
                'role' => User::ROLE_PROJECT_MANAGER,
                'is_active' => true,
            ];

            $clonedProject->members()->sync($members);

            if (! $withTasks) {
                return $clonedProject;
            }

            $taskMap = [];

            $sourceTasks = $project->tasks
                ->sortBy('position')
                ->values();

            foreach ($sourceTasks as $sourceTask) {
                $clonedTask = $sourceTask->replicate(['completed_at']);
                $clonedTask->project_id = $clonedProject->id;
                $clonedTask->status = Task::STATUS_TODO;
                $clonedTask->is_in_review = false;
                $clonedTask->completed_at = null;
                $clonedTask->save();

                if ($sourceTask->relationLoaded('tags')) {
                    $clonedTask->tags()->sync($sourceTask->tags->pluck('id')->all());
                }

                if ($sourceTask->relationLoaded('subtasks')) {
                    $subtasksData = $sourceTask->subtasks
                        ->map(fn (TaskSubtask $subtask): array => [
                            'title' => $subtask->title,
                            'is_completed' => false,
                            'completed_by' => null,
                            'completed_at' => null,
                            'position' => $subtask->position,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                        ->all();

                    $clonedTask->subtasks()->createMany($subtasksData);
                }

                $taskMap[$sourceTask->id] = $clonedTask->id;
            }

            foreach ($sourceTasks as $sourceTask) {
                $clonedTaskId = $taskMap[$sourceTask->id] ?? null;
                if (! $clonedTaskId) {
                    continue;
                }

                $dependencyIds = $sourceTask->dependencies
                    ->pluck('id')
                    ->map(fn (int $sourceDependencyId): ?int => $taskMap[$sourceDependencyId] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                if ($dependencyIds !== []) {
                    Task::query()->find($clonedTaskId)?->dependencies()->sync($dependencyIds);
                }
            }

            return $clonedProject;
        });

        return redirect()
            ->route('client.projects.show', $newProject)
            ->with('status', 'Projet duplique avec succes.');
    }

    /**
     * @return array{0:string,1:bool,2:string}
     */
    private function resolveTargetState(string $targetStatus, ?string $targetLane): array
    {
        $targetLane ??= $targetStatus;

        if ($targetLane === self::REVIEW_LANE) {
            return [Task::STATUS_DOING, true, self::REVIEW_LANE];
        }

        if ($targetLane === Task::STATUS_DOING) {
            return [Task::STATUS_DOING, false, Task::STATUS_DOING];
        }

        if ($targetLane === Task::STATUS_DONE) {
            return [Task::STATUS_DONE, false, Task::STATUS_DONE];
        }

        return [Task::STATUS_TODO, false, Task::STATUS_TODO];
    }

    private function laneScopedQuery(int $projectId, string $status, bool $isInReview): \Illuminate\Database\Eloquent\Builder
    {
        $query = Task::query()
            ->where('project_id', $projectId)
            ->where('status', $status);

        if ($status === Task::STATUS_DOING) {
            $query->where('is_in_review', $isInReview);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProjectPayload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'objective' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Project::statusOptions()))],
            'priority' => ['required', Rule::in(array_keys(Project::priorityOptions()))],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'is_template' => ['nullable', 'boolean'],
            'template_name' => ['nullable', 'string', 'max:180'],
        ]);

        $isTemplate = (bool) ($validated['is_template'] ?? false);
        $templateName = $validated['template_name'] ?? null;
        if (! $isTemplate) {
            $templateName = null;
        }

        return [
            'name' => trim((string) $validated['name']),
            'description' => $validated['description'] ?? null,
            'objective' => $validated['objective'] ?? null,
            'status' => (string) $validated['status'],
            'priority' => (string) $validated['priority'],
            'budget' => $validated['budget'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'client_id' => $validated['client_id'] ?? null,
            'is_template' => $isTemplate,
            'template_name' => $templateName ? trim((string) $templateName) : null,
        ];
    }
}
