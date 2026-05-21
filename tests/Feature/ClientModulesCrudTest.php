<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientModulesCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_crud_users_and_teams_from_client_module(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->post(route('client.users.store'), [
                'name' => 'Nouveau Membre',
                'email' => 'nouveau.membre@fal-pms.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => User::ROLE_MEMBER,
                'is_active' => 1,
                'notify_email' => 1,
                'notify_realtime' => 1,
                'notify_push' => 0,
            ])
            ->assertRedirect(route('client.users.index'));

        $createdUser = User::query()
            ->where('email', 'nouveau.membre@fal-pms.test')
            ->firstOrFail();

        $this->assertDatabaseHas('users', [
            'id' => $createdUser->id,
            'role' => User::ROLE_MEMBER,
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->patch(route('client.users.update', $createdUser), [
                'name' => 'Nouveau Membre MAJ',
                'email' => 'nouveau.membre@fal-pms.test',
                'role' => User::ROLE_CLIENT,
                'is_active' => 1,
                'notify_email' => 1,
                'notify_realtime' => 1,
                'notify_push' => 1,
            ])
            ->assertRedirect(route('client.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $createdUser->id,
            'name' => 'Nouveau Membre MAJ',
            'role' => User::ROLE_CLIENT,
        ]);

        $this->actingAs($manager)
            ->post(route('client.teams.store'), [
                'name' => 'Equipe Client Ops',
                'description' => 'Equipe test pour workflow CRUD',
                'is_active' => 1,
                'member_ids' => [$createdUser->id],
            ])
            ->assertRedirect();

        $team = Team::query()
            ->where('name', 'Equipe Client Ops')
            ->firstOrFail();

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'owner_id' => $manager->id,
        ]);

        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $manager->id,
            'role' => User::ROLE_PROJECT_MANAGER,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $createdUser->id,
            'role' => User::ROLE_MEMBER,
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->patch(route('client.teams.members.update', [$team, $createdUser]), [
                'role' => User::ROLE_MANAGER,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $createdUser->id,
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->delete(route('client.teams.members.remove', [$team, $createdUser]))
            ->assertRedirect();

        $this->assertDatabaseMissing('team_user', [
            'team_id' => $team->id,
            'user_id' => $createdUser->id,
        ]);

        $this->actingAs($manager)
            ->delete(route('client.teams.destroy', $team))
            ->assertRedirect(route('client.teams.index'));

        $this->assertDatabaseMissing('teams', [
            'id' => $team->id,
        ]);

        $this->actingAs($manager)
            ->delete(route('client.users.destroy', $createdUser))
            ->assertRedirect(route('client.users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $createdUser->id,
        ]);
    }

    public function test_dashboard_snapshot_contains_new_operational_indicators(): void
    {
        $manager = User::factory()->manager()->create();
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
            ->create([
                'status' => Task::STATUS_TODO,
                'due_date' => now()->addDays(2),
            ]);

        $team = Team::query()->create([
            'owner_id' => $manager->id,
            'name' => 'Equipe Snapshot',
            'is_active' => true,
        ]);

        $team->members()->syncWithoutDetaching([
            $manager->id => ['role' => User::ROLE_PROJECT_MANAGER, 'is_active' => true],
            $member->id => ['role' => User::ROLE_MEMBER, 'is_active' => true],
        ]);

        Timesheet::query()->create([
            'user_id' => $manager->id,
            'project_id' => $project->id,
            'task_id' => null,
            'work_date' => now()->toDateString(),
            'hours' => 4.5,
            'note' => 'Suivi hebdo',
        ]);

        $this->actingAs($manager)
            ->get(route('client.dashboard.snapshot'))
            ->assertOk()
            ->assertJsonStructure([
                'stats' => [
                    'tasks_due_week',
                    'users_total',
                    'users_active',
                    'teams_total',
                    'teams_active',
                    'timesheet_hours_week',
                ],
                'usersPreview',
                'teamsPreview',
            ]);
    }
}
