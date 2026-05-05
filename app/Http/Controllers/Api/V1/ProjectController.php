<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $projects = $user->visibleProjectsQuery()
            ->withCount([
                'tasks',
                'tasks as tasks_done_count' => fn ($query) => $query->where('status', 'done'),
            ])
            ->orderByDesc('updated_at')
            ->paginate(15);

        return response()->json($projects);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);

        $project->load([
            'owner:id,name,email',
            'members:id,name,email',
        ]);

        return response()->json([
            'project' => $project,
        ]);
    }
}

