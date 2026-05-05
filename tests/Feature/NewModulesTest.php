<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Timesheet;
use App\Models\User;
use App\Notifications\UserMentionedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class NewModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_mention_creates_record_and_notification(): void
    {
        $owner = User::factory()->projectManager()->create();
        $commenter = User::factory()->create();
        $mentioned = User::factory()->create();

        $project = Project::factory()
            ->for($owner, 'owner')
            ->create();

        $project->members()->syncWithoutDetaching([
            $commenter->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
            $mentioned->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
        ]);

        $task = Task::factory()
            ->for($project)
            ->for($commenter, 'assignee')
            ->create();

        Notification::fake();

        $handle = Str::before($mentioned->email, '@');
        $comment = TaskComment::query()->create([
            'task_id' => $task->id,
            'user_id' => $commenter->id,
            'body' => 'Merci de verifier ce point @'.$handle,
        ]);

        $this->assertDatabaseHas('comment_mentions', [
            'task_comment_id' => $comment->id,
            'user_id' => $mentioned->id,
        ]);

        Notification::assertSentTo($mentioned, UserMentionedNotification::class);
    }

    public function test_api_mobile_flow_login_and_quick_task_action(): void
    {
        $member = User::factory()->create([
            'password' => bcrypt('password'),
        ]);
        $owner = User::factory()->projectManager()->create();

        $project = Project::factory()
            ->for($owner, 'owner')
            ->create();

        $project->members()->syncWithoutDetaching([
            $member->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
        ]);

        $task = Task::factory()
            ->for($project)
            ->for($member, 'assignee')
            ->create([
                'status' => Task::STATUS_TODO,
            ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $member->email,
            'password' => 'password',
            'device_name' => 'flutter',
        ])->assertOk();

        $token = (string) $loginResponse->json('token');

        $this->patchJson('/api/v1/tasks/'.$task->id.'/status', [
            'status' => Task::STATUS_DOING,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Task::STATUS_DOING,
        ]);
    }

    public function test_project_file_upload_uses_incremental_versioning(): void
    {
        Storage::fake('local');

        $manager = User::factory()->projectManager()->create();
        $project = Project::factory()
            ->for($manager, 'owner')
            ->create();

        $this->actingAs($manager);

        $payload = [
            'logical_name' => 'spec_api_mobile',
            'file' => UploadedFile::fake()->create('spec-v1.pdf', 50, 'application/pdf'),
        ];

        $this->post(route('client.projects.files.store', $project), $payload)
            ->assertRedirect();

        $payload['file'] = UploadedFile::fake()->create('spec-v2.pdf', 70, 'application/pdf');
        $this->post(route('client.projects.files.store', $project), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('project_files', [
            'project_id' => $project->id,
            'logical_name' => 'spec_api_mobile',
            'version' => 1,
        ]);
        $this->assertDatabaseHas('project_files', [
            'project_id' => $project->id,
            'logical_name' => 'spec_api_mobile',
            'version' => 2,
        ]);
    }

    public function test_member_can_store_timesheet_entry(): void
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
            ->post(route('client.timesheets.store'), [
                'project_id' => $project->id,
                'work_date' => now()->toDateString(),
                'hours' => 3.5,
                'note' => 'Sprint mobile Flutter',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('timesheets', [
            'user_id' => $member->id,
            'project_id' => $project->id,
            'hours' => '3.50',
        ]);
    }

    public function test_api_member_can_update_and_delete_own_timesheet_entry(): void
    {
        $manager = User::factory()->projectManager()->create();
        $member = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $project = Project::factory()
            ->for($manager, 'owner')
            ->create();

        $project->members()->syncWithoutDetaching([
            $member->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
        ]);

        $timesheet = Timesheet::query()->create([
            'user_id' => $member->id,
            'project_id' => $project->id,
            'task_id' => null,
            'work_date' => now()->toDateString(),
            'hours' => 3.5,
            'note' => 'Initial mobile note',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $member->email,
            'password' => 'password',
            'device_name' => 'flutter',
        ])->assertOk();

        $token = (string) $loginResponse->json('token');

        $this->patchJson('/api/v1/timesheets/'.$timesheet->id, [
            'work_date' => now()->subDay()->toDateString(),
            'hours' => 4.5,
            'note' => 'Mise a jour mobile',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertOk()
            ->assertJsonPath('entry.id', $timesheet->id);

        $this->assertDatabaseHas('timesheets', [
            'id' => $timesheet->id,
            'hours' => '4.50',
            'note' => 'Mise a jour mobile',
        ]);

        $this->deleteJson('/api/v1/timesheets/'.$timesheet->id, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->assertDatabaseMissing('timesheets', [
            'id' => $timesheet->id,
        ]);
    }

    public function test_api_member_cannot_update_other_user_timesheet_entry(): void
    {
        $manager = User::factory()->projectManager()->create();
        $member = User::factory()->create([
            'password' => bcrypt('password'),
        ]);
        $otherMember = User::factory()->create();

        $project = Project::factory()
            ->for($manager, 'owner')
            ->create();

        $project->members()->syncWithoutDetaching([
            $member->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
            $otherMember->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
        ]);

        $timesheet = Timesheet::query()->create([
            'user_id' => $otherMember->id,
            'project_id' => $project->id,
            'task_id' => null,
            'work_date' => now()->toDateString(),
            'hours' => 2.5,
            'note' => 'Autre utilisateur',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $member->email,
            'password' => 'password',
            'device_name' => 'flutter',
        ])->assertOk();

        $token = (string) $loginResponse->json('token');

        $this->patchJson('/api/v1/timesheets/'.$timesheet->id, [
            'work_date' => now()->toDateString(),
            'hours' => 6,
            'note' => 'Tentative non autorisee',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertForbidden();

        $this->assertDatabaseHas('timesheets', [
            'id' => $timesheet->id,
            'hours' => '2.50',
        ]);
    }

    public function test_member_can_open_client_dashboard(): void
    {
        $manager = User::factory()->projectManager()->create();
        $member = User::factory()->create();
        $project = Project::factory()
            ->for($manager, 'owner')
            ->create();

        $project->members()->syncWithoutDetaching([
            $member->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
        ]);

        Task::factory()
            ->for($project)
            ->for($member, 'assignee')
            ->create();

        $this->actingAs($member)
            ->get(route('client.dashboard'))
            ->assertOk();
    }
}
