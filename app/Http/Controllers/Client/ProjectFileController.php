<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use App\Notifications\ProjectFileSharedNotification;
use App\Support\Realtime\DashboardBroadcaster;
use App\Support\Realtime\WorkspaceBroadcaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectFileController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);
        abort_unless($user->hasPermission('files.create'), 403);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'task_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'logical_name' => ['nullable', 'string', 'max:180'],
        ]);

        $taskId = (int) ($validated['task_id'] ?? 0);
        if ($taskId > 0) {
            $belongs = $project->tasks()->where('id', $taskId)->exists();
            abort_unless($belongs, 422, 'La tache selectionnee n appartient pas au projet.');
        }

        $uploaded = $request->file('file');
        $originalName = basename((string) $uploaded->getClientOriginalName());
        $baseName = $validated['logical_name']
            ?? pathinfo($originalName, PATHINFO_FILENAME);
        $logicalName = Str::of((string) $baseName)
            ->slug('_')
            ->value();
        $logicalName = $logicalName !== '' ? $logicalName : 'document';

        $nextVersion = (int) ProjectFile::query()
            ->where('project_id', $project->id)
            ->where('logical_name', $logicalName)
            ->max('version') + 1;

        $disk = (string) config('filesystems.project_files_disk', config('filesystems.default'));
        $storedFilename = 'v'.$nextVersion.'_'.Str::random(10).'_'.$originalName;
        $storedPath = $uploaded->storeAs(
            'projects/'.$project->id.'/files/'.$logicalName,
            $storedFilename,
            $disk,
        );

        $file = ProjectFile::query()->create([
            'project_id' => $project->id,
            'task_id' => $taskId > 0 ? $taskId : null,
            'uploaded_by' => $user->id,
            'logical_name' => $logicalName,
            'version' => max(1, $nextVersion),
            'original_name' => $originalName,
            'stored_path' => (string) $storedPath,
            'mime_type' => $uploaded->getClientMimeType(),
            'size' => (int) $uploaded->getSize(),
        ]);

        $recipients = $project->members()
            ->wherePivot('is_active', true)
            ->get()
            ->push($project->owner)
            ->filter(fn (mixed $recipient): bool => $recipient instanceof User && ! $recipient->is($user))
            ->unique('id')
            ->values();

        foreach ($recipients as $recipient) {
            $recipient->notify(new ProjectFileSharedNotification($project, $file, $user));
        }

        WorkspaceBroadcaster::forProject($project, 'project_file_shared', [
            'file_id' => $file->id,
        ]);
        DashboardBroadcaster::forProject($project, [$user->id], 'project_file_shared', [
            'file_id' => $file->id,
        ]);

        return back()->with('status', 'Fichier partage avec versioning active.');
    }

    public function download(Request $request, Project $project, ProjectFile $projectFile): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);
        abort_unless($user->hasPermission('files.read'), 403);
        abort_unless($projectFile->project_id === $project->id, 404);

        $disk = (string) config('filesystems.project_files_disk', config('filesystems.default'));

        return Storage::disk($disk)->download($projectFile->stored_path, $projectFile->original_name);
    }

    public function destroy(Request $request, Project $project, ProjectFile $projectFile): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);
        abort_unless($user->hasPermission('files.create'), 403);
        abort_unless($projectFile->project_id === $project->id, 404);

        $canManage = $user->canManageProject($project);
        $isUploader = (int) $projectFile->uploaded_by === (int) $user->id;
        abort_unless($canManage || $isUploader, 403);

        $disk = (string) config('filesystems.project_files_disk', config('filesystems.default'));
        if (Storage::disk($disk)->exists($projectFile->stored_path)) {
            Storage::disk($disk)->delete($projectFile->stored_path);
        }

        $fileId = (int) $projectFile->id;
        $projectFile->delete();

        WorkspaceBroadcaster::forProject($project, 'project_file_deleted', [
            'file_id' => $fileId,
        ]);
        DashboardBroadcaster::forProject($project, [$user->id], 'project_file_deleted', [
            'file_id' => $fileId,
        ]);

        return back()->with('status', 'Fichier supprime.');
    }
}
