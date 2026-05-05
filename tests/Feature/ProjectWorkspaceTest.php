<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_member_can_open_report_and_download_json(): void
    {
        $manager = User::factory()->projectManager()->create();
        $member = User::factory()->create();

        $project = Project::factory()
            ->for($manager, 'owner')
            ->create();

        $project->members()->syncWithoutDetaching([
            $member->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
        ]);

        $this->actingAs($member);

        $this->get(route('client.projects.report', $project))
            ->assertOk()
            ->assertSee($project->name);

        $this->get(route('client.projects.report.download', $project))
            ->assertOk()
            ->assertHeader('content-type', 'application/json');
    }

    public function test_project_member_can_download_pdf_report(): void
    {
        $manager = User::factory()->projectManager()->create();
        $member = User::factory()->create();

        $project = Project::factory()
            ->for($manager, 'owner')
            ->create();

        $project->members()->syncWithoutDetaching([
            $member->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
        ]);

        $this->actingAs($member)
            ->get(route('client.projects.report.download-pdf', $project))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_drag_drop_endpoint_updates_task_status(): void
    {
        $manager = User::factory()->projectManager()->create();
        $member = User::factory()->create();

        $project = Project::factory()
            ->for($manager, 'owner')
            ->create();

        $project->members()->syncWithoutDetaching([
            $member->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
        ]);

        $task = Task::factory()->for($project)->create([
            'status' => Task::STATUS_TODO,
            'position' => 1,
        ]);

        $this->actingAs($member)
            ->postJson(route('client.projects.tasks.move', [$project, $task]), [
                'status' => Task::STATUS_DOING,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', Task::STATUS_DOING);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Task::STATUS_DOING,
        ]);
    }

    public function test_drag_drop_endpoint_reorders_task_in_same_column(): void
    {
        $manager = User::factory()->projectManager()->create();
        $member = User::factory()->create();

        $project = Project::factory()
            ->for($manager, 'owner')
            ->create();

        $project->members()->syncWithoutDetaching([
            $member->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
        ]);

        $firstTask = Task::factory()->for($project)->create([
            'status' => Task::STATUS_TODO,
            'position' => 1,
        ]);
        $secondTask = Task::factory()->for($project)->create([
            'status' => Task::STATUS_TODO,
            'position' => 2,
        ]);

        $this->actingAs($member)
            ->postJson(route('client.projects.tasks.move', [$project, $secondTask]), [
                'status' => Task::STATUS_TODO,
                'position' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', Task::STATUS_TODO)
            ->assertJsonPath('position', 1);

        $this->assertDatabaseHas('tasks', [
            'id' => $secondTask->id,
            'status' => Task::STATUS_TODO,
            'position' => 1,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $firstTask->id,
            'status' => Task::STATUS_TODO,
            'position' => 2,
        ]);
    }

    public function test_drag_drop_endpoint_moves_task_to_review_lane(): void
    {
        $manager = User::factory()->projectManager()->create();
        $member = User::factory()->create();

        $project = Project::factory()
            ->for($manager, 'owner')
            ->create();

        $project->members()->syncWithoutDetaching([
            $member->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
        ]);

        $task = Task::factory()->for($project)->create([
            'status' => Task::STATUS_DOING,
            'is_in_review' => false,
            'position' => 1,
        ]);

        $this->actingAs($member)
            ->postJson(route('client.projects.tasks.move', [$project, $task]), [
                'status' => Task::STATUS_DOING,
                'lane' => 'review',
                'position' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', Task::STATUS_DOING)
            ->assertJsonPath('lane', 'review');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Task::STATUS_DOING,
            'is_in_review' => true,
            'position' => 1,
        ]);
    }

    public function test_non_member_cannot_access_workspace(): void
    {
        $manager = User::factory()->projectManager()->create();
        $outsider = User::factory()->create();

        $project = Project::factory()
            ->for($manager, 'owner')
            ->create();

        $this->actingAs($outsider)
            ->get(route('client.projects.show', $project))
            ->assertForbidden();
    }
}
