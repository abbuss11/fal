<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission('timesheets.read'), 403);

        $month = $request->string('month')->toString();
        try {
            $currentMonth = $month !== ''
                ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
                : now()->startOfMonth();
        } catch (InvalidFormatException) {
            $currentMonth = now()->startOfMonth();
        }

        $start = $currentMonth->copy()->startOfMonth();
        $end = $currentMonth->copy()->endOfMonth();

        $scope = $request->string('scope')->toString();
        $teamMode = $scope === 'team' && ($user->isAdmin() || $user->hasPermission('timesheets.update'));

        $query = Timesheet::query()
            ->with(['project', 'task', 'user'])
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()]);

        if ($teamMode) {
            $visibleProjectIds = $user->visibleProjectsQuery()->pluck('id');
            $query->whereIn('project_id', $visibleProjectIds);
        } else {
            $query->where('user_id', $user->id);
        }

        $entries = $query
            ->orderByDesc('work_date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $totalHours = (clone $query)->sum('hours');
        $hoursByProject = (clone $query)
            ->selectRaw('project_id, SUM(hours) as total_hours')
            ->groupBy('project_id')
            ->with('project')
            ->get();

        $projects = $user->visibleProjectsQuery()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('client.timesheets.index', [
            'entries' => $entries,
            'projects' => $projects,
            'currentMonth' => $currentMonth,
            'teamMode' => $teamMode,
            'totalHours' => (float) $totalHours,
            'hoursByProject' => $hoursByProject,
        ]);
    }

    public function store(Request $request): RedirectResponse
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
            $taskBelongsToProject = $project->tasks()->where('id', $taskId)->exists();
            abort_unless($taskBelongsToProject, 422, 'La tache ne correspond pas au projet selectionne.');
        }

        Timesheet::query()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'task_id' => $taskId > 0 ? $taskId : null,
            'work_date' => $validated['work_date'],
            'hours' => $validated['hours'],
            'note' => $validated['note'] ?? null,
        ]);

        return back()->with('status', 'Temps enregistre avec succes.');
    }

    public function update(Request $request, Timesheet $timesheet): RedirectResponse
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

        return back()->with('status', 'Entree timesheet mise a jour.');
    }

    public function destroy(Request $request, Timesheet $timesheet): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $canDelete = $timesheet->user_id === $user->id
            || $user->isAdmin()
            || $user->hasPermission('timesheets.update');
        abort_unless($canDelete, 403);

        $timesheet->delete();

        return back()->with('status', 'Entree timesheet supprimee.');
    }
}

