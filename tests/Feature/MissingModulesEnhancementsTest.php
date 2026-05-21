<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskSubtask;
use App\Models\TaskTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissingModulesEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_manager_can_archive_unarchive_and_duplicate_project(): void
    {
        $manager = User::factory()->projectManager()->create();
        $member = User::factory()->create();

        $project = Project::factory()
            ->for($manager, 'owner')
            ->create([
                'name' => 'Projet source',
            ]);

        $project->members()->syncWithoutDetaching([
            $manager->id => ['role' => User::ROLE_PROJECT_MANAGER, 'is_active' => true],
            $member->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
        ]);

        $taskA = Task::factory()
            ->for($project)
            ->for($member, 'assignee')
            ->create([
                'title' => 'Task A',
                'status' => Task::STATUS_TODO,
                'position' => 1,
            ]);

        $taskB = Task::factory()
            ->for($project)
            ->for($member, 'assignee')
            ->create([
                'title' => 'Task B',
                'status' => Task::STATUS_DOING,
                'position' => 2,
            ]);

        $tag = TaskTag::query()->create([
            'name' => 'API',
            'color' => '#0ea5e9',
        ]);

        $taskB->tags()->sync([$tag->id]);
        $taskB->dependencies()->sync([$taskA->id]);
        TaskSubtask::query()->create([
            'task_id' => $taskB->id,
            'title' => 'Sous-tache B1',
            'position' => 1,
        ]);

        $this->actingAs($manager)
            ->post(route('client.projects.archive', $project))
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'is_archived' => true,
        ]);

        $this->actingAs($manager)
            ->post(route('client.projects.unarchive', $project))
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'is_archived' => false,
        ]);

        $this->actingAs($manager)
            ->post(route('client.projects.duplicate', $project), [
                'name' => 'Projet copie test',
                'with_tasks' => true,
            ])
            ->assertRedirect();

        $copiedProject = Project::query()
            ->where('name', 'Projet copie test')
            ->first();

        $this->assertNotNull($copiedProject);
        $this->assertSame(Project::STATUS_PLANNING, $copiedProject->status);

        $copiedTasks = Task::query()
            ->where('project_id', $copiedProject->id)
            ->orderBy('title')
            ->get();

        $this->assertCount(2, $copiedTasks);
        $this->assertTrue($copiedTasks->every(fn (Task $task): bool => $task->status === Task::STATUS_TODO));

        $copiedTaskB = $copiedTasks->firstWhere('title', 'Task B');
        $this->assertNotNull($copiedTaskB);
        $this->assertCount(1, $copiedTaskB->tags);
        $this->assertCount(1, $copiedTaskB->subtasks);
        $this->assertCount(1, $copiedTaskB->dependencies);
    }

    public function test_user_can_update_notification_preferences_from_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'job_title' => $user->job_title,
                'phone' => $user->phone,
                'bio' => $user->bio,
                'notify_email' => 0,
                'notify_realtime' => 1,
                'notify_push' => 0,
            ])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertFalse($user->notify_email);
        $this->assertTrue($user->notify_realtime);
        $this->assertFalse($user->notify_push);
    }

    public function test_task_done_transition_is_blocked_when_dependencies_are_open(): void
    {
        $owner = User::factory()->projectManager()->create();
        $assignee = User::factory()->create();

        $project = Project::factory()
            ->for($owner, 'owner')
            ->create();

        $project->members()->syncWithoutDetaching([
            $assignee->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
        ]);

        $blockedBy = Task::factory()
            ->for($project)
            ->for($assignee, 'assignee')
            ->create([
                'status' => Task::STATUS_TODO,
                'title' => 'Blocante',
            ]);

        $dependent = Task::factory()
            ->for($project)
            ->for($assignee, 'assignee')
            ->create([
                'status' => Task::STATUS_DOING,
                'title' => 'Dependante',
            ]);

        $dependent->dependencies()->sync([$blockedBy->id]);

        $dependent->update([
            'status' => Task::STATUS_DONE,
        ]);

        $dependent->refresh();

        $this->assertSame(Task::STATUS_DOING, $dependent->status);
        $this->assertTrue((bool) $dependent->is_in_review);
    }
}

