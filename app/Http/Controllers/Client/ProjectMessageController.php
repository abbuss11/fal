<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectMessagePostedNotification;
use App\Notifications\UserMentionedNotification;
use App\Services\MentionResolverService;
use App\Support\Realtime\DashboardBroadcaster;
use App\Support\Realtime\WorkspaceBroadcaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectMessageController extends Controller
{
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
            $recipient->notify(new ProjectMessagePostedNotification($project, $message, $user));
        }

        $excerpt = Str::limit($validated['body'], 180);
        foreach ($mentionedUsers as $mentionedUser) {
            $mentionedUser->notify(new UserMentionedNotification(
                project: $project,
                contextLabel: 'le chat interne du projet',
                excerpt: $excerpt,
                mentionedBy: $user,
            ));
        }

        WorkspaceBroadcaster::forProject($project, 'project_message_posted', [
            'message_id' => $message->id,
        ]);
        DashboardBroadcaster::forProject($project, [$user->id], 'project_message_posted', [
            'message_id' => $message->id,
        ]);

        return back()->with('status', 'Message interne envoye.');
    }
}

