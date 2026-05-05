<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskCommentAddedNotification;
use App\Notifications\TaskStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CollaborationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_change_sends_notification_and_logs_activity(): void
    {
        $actor = User::factory()->admin()->create();
        $owner = User::factory()->projectManager()->create();
        $initialAssignee = User::factory()->create();
        $newAssignee = User::factory()->create();

        $project = $this->createProjectWithMembers($owner, [$initialAssignee, $newAssignee]);

        $task = Task::factory()
            ->for($project)
            ->for($initialAssignee, 'assignee')
            ->create([
                'status' => Task::STATUS_TODO,
            ]);

        Notification::fake();

        $this->actingAs($actor);

        $task->update([
            'assigned_to' => $newAssignee->id,
        ]);

        Notification::assertSentTo($newAssignee, TaskAssignedNotification::class);
        Notification::assertNotSentTo($actor, TaskAssignedNotification::class);

        $this->assertDatabaseHas('activity_logs', [
            'task_id' => $task->id,
            'user_id' => $actor->id,
            'action' => 'task_assigned',
        ]);
    }

    public function test_status_change_updates_completion_and_notifies_stakeholders(): void
    {
        $owner = User::factory()->projectManager()->create();
        $assignee = User::factory()->create();
        $member = User::factory()->create();

        $project = $this->createProjectWithMembers($owner, [$assignee, $member]);

        $task = Task::factory()
            ->for($project)
            ->for($assignee, 'assignee')
            ->create([
                'status' => Task::STATUS_TODO,
                'completed_at' => null,
            ]);

        Notification::fake();

        $this->actingAs($owner);

        $task->update([
            'status' => Task::STATUS_DONE,
        ]);

        $task->refresh();

        $this->assertNotNull($task->completed_at);

        Notification::assertSentTo($assignee, TaskStatusUpdatedNotification::class);
        Notification::assertSentTo($member, TaskStatusUpdatedNotification::class);
        Notification::assertNotSentTo($owner, TaskStatusUpdatedNotification::class);

        $this->assertDatabaseHas('activity_logs', [
            'task_id' => $task->id,
            'user_id' => $owner->id,
            'action' => 'task_status_changed',
        ]);
    }

    public function test_new_comment_logs_activity_and_notifies_other_members(): void
    {
        $owner = User::factory()->projectManager()->create();
        $assignee = User::factory()->create();
        $commenter = User::factory()->create();
        $observerMember = User::factory()->create();

        $project = $this->createProjectWithMembers($owner, [$assignee, $commenter, $observerMember]);

        $task = Task::factory()
            ->for($project)
            ->for($assignee, 'assignee')
            ->create([
                'status' => Task::STATUS_DOING,
            ]);

        Notification::fake();

        $comment = TaskComment::query()->create([
            'task_id' => $task->id,
            'user_id' => $commenter->id,
            'body' => 'Le prototype est pret pour revue.',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'task_id' => $task->id,
            'user_id' => $commenter->id,
            'action' => 'task_comment_added',
        ]);

        Notification::assertSentTo($owner, TaskCommentAddedNotification::class);
        Notification::assertSentTo($assignee, TaskCommentAddedNotification::class);
        Notification::assertSentTo($observerMember, TaskCommentAddedNotification::class);
        Notification::assertNotSentTo($commenter, TaskCommentAddedNotification::class);

        $this->assertSame($commenter->id, $comment->user_id);
    }

    /**
     * @param array<int, User> $members
     */
    private function createProjectWithMembers(User $owner, array $members): Project
    {
        $project = Project::factory()
            ->for($owner, 'owner')
            ->create();

        $project->members()->syncWithoutDetaching([
            $owner->id => ['role' => User::ROLE_PROJECT_MANAGER],
        ]);

        foreach ($members as $member) {
            $project->members()->syncWithoutDetaching([
                $member->id => ['role' => User::ROLE_MEMBER],
            ]);
        }

        return $project;
    }
}

