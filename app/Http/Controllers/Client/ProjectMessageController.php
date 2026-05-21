<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use App\Notifications\ProjectMessagePostedNotification;
use App\Notifications\UserMentionedNotification;
use App\Services\MentionResolverService;
use App\Support\Notifications\SendsNotificationsSafely;
use App\Support\Realtime\DashboardBroadcaster;
use App\Support\Realtime\WorkspaceBroadcaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectMessageController extends Controller
{
    use SendsNotificationsSafely;

    public function __construct(private readonly MentionResolverService $mentionResolver) {}

    public function store(Request $request, Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);
        abort_unless($user->hasPermission('messages.create'), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $mentionedUsers = $this->mentionResolver->resolveUsers(
            text: $validated['body'],
            project: $project,
            authorId: $user->id,
        );

        $message = $project->messages()->create([
            'user_id' => $user->id,
            'body' => $validated['body'],
            'mentions' => $mentionedUsers->pluck('id')->values()->all(),
        ]);

        $recipients = $project->members()
            ->wherePivot('is_active', true)
            ->get()
            ->push($project->owner)
            ->filter(fn (mixed $recipient): bool => $recipient instanceof User && ! $recipient->is($user))
            ->unique('id')
            ->values();

        foreach ($recipients as $recipient) {
            $this->notifySafely($recipient, new ProjectMessagePostedNotification($project, $message, $user), [
                'context' => 'project_message_posted',
                'project_id' => $project->id,
                'message_id' => $message->id,
            ]);
        }

        $excerpt = Str::limit($validated['body'], 180);
        foreach ($mentionedUsers as $mentionedUser) {
            $this->notifySafely($mentionedUser, new UserMentionedNotification(
                project: $project,
                contextLabel: 'le chat interne du projet',
                excerpt: $excerpt,
                mentionedBy: $user,
            ), [
                'context' => 'project_message_mention',
                'project_id' => $project->id,
                'message_id' => $message->id,
            ]);
        }

        WorkspaceBroadcaster::forProject($project, 'project_message_posted', [
            'message_id' => $message->id,
        ]);
        DashboardBroadcaster::forProject($project, [$user->id], 'project_message_posted', [
            'message_id' => $message->id,
        ]);

        return back()->with('status', 'Message interne envoye.');
    }

    public function update(Request $request, Project $project, ProjectMessage $projectMessage): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);
        abort_unless($user->hasPermission('messages.create'), 403);
        abort_unless((int) $projectMessage->project_id === (int) $project->id, 404);

        $canManage = $user->canManageProject($project);
        $isAuthor = (int) $projectMessage->user_id === (int) $user->id;
        abort_unless($canManage || $isAuthor, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $mentionedUsers = $this->mentionResolver->resolveUsers(
            text: $validated['body'],
            project: $project,
            authorId: $user->id,
        );

        $projectMessage->update([
            'body' => $validated['body'],
            'mentions' => $mentionedUsers->pluck('id')->values()->all(),
        ]);

        $excerpt = Str::limit($validated['body'], 180);
        foreach ($mentionedUsers as $mentionedUser) {
            $this->notifySafely($mentionedUser, new UserMentionedNotification(
                project: $project,
                contextLabel: 'le chat interne du projet',
                excerpt: $excerpt,
                mentionedBy: $user,
            ), [
                'context' => 'project_message_updated_mention',
                'project_id' => $project->id,
                'message_id' => $projectMessage->id,
            ]);
        }

        WorkspaceBroadcaster::forProject($project, 'project_message_updated', [
            'message_id' => $projectMessage->id,
        ]);
        DashboardBroadcaster::forProject($project, [$user->id], 'project_message_updated', [
            'message_id' => $projectMessage->id,
        ]);

        return back()->with('status', 'Message mis a jour.');
    }

    public function destroy(Request $request, Project $project, ProjectMessage $projectMessage): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);
        abort_unless($user->hasPermission('messages.create'), 403);
        abort_unless((int) $projectMessage->project_id === (int) $project->id, 404);

        $canManage = $user->canManageProject($project);
        $isAuthor = (int) $projectMessage->user_id === (int) $user->id;
        abort_unless($canManage || $isAuthor, 403);

        $messageId = (int) $projectMessage->id;
        $projectMessage->delete();

        WorkspaceBroadcaster::forProject($project, 'project_message_deleted', [
            'message_id' => $messageId,
        ]);
        DashboardBroadcaster::forProject($project, [$user->id], 'project_message_deleted', [
            'message_id' => $messageId,
        ]);

        return back()->with('status', 'Message supprime.');
    }
}
