<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@fal-pms.test'],
            [
                'name' => 'Admin FAL PMS',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => true,
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'last_seen_at' => now(),
            ]
        );

        $projectManagerDemo = User::query()->updateOrCreate(
            ['email' => 'pm@fal-pms.test'],
            [
                'name' => 'Chef Projet FAL',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'role' => User::ROLE_PROJECT_MANAGER,
                'is_active' => true,
                'last_seen_at' => now(),
            ]
        );

        $memberDemo = User::query()->updateOrCreate(
            ['email' => 'member@fal-pms.test'],
            [
                'name' => 'Membre Equipe FAL',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'role' => User::ROLE_MEMBER,
                'is_active' => true,
                'last_seen_at' => now(),
            ]
        );

        $projectManagers = User::factory(4)->projectManager()->create();
        $members = User::factory(12)->create();
        $owners = $projectManagers
            ->push($projectManagerDemo)
            ->push($admin);
        $members->push($memberDemo);

        for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
            $projectsInMonth = fake()->numberBetween(3, 5);
            $monthStart = now()->startOfMonth()->subMonths($monthOffset);
            $monthEnd = (clone $monthStart)->endOfMonth();

            for ($i = 0; $i < $projectsInMonth; $i++) {
                $status = fake()->randomElement([
                    'planning',
                    'active',
                    'active',
                    'on_hold',
                    'completed',
                    'cancelled',
                ]);

                $startDate = Carbon::instance(fake()->dateTimeBetween($monthStart, $monthEnd));
                $dueDate = (clone $startDate)->addDays(fake()->numberBetween(15, 90));
                $completedAt = $status === 'completed'
                    ? (clone $startDate)->addDays(fake()->numberBetween(10, 80))
                    : null;

                /** @var User $owner */
                $owner = $owners->random();

                $project = Project::factory()
                    ->for($owner, 'owner')
                    ->create([
                        'status' => $status,
                        'priority' => fake()->randomElement(['low', 'medium', 'high']),
                        'start_date' => $startDate,
                        'due_date' => $dueDate,
                        'completed_at' => $completedAt,
                        'created_at' => Carbon::instance(fake()->dateTimeBetween($monthStart, $monthEnd)),
                    ]);

                $projectMemberIds = $members
                    ->random(fake()->numberBetween(4, 8))
                    ->pluck('id')
                    ->all();

                $project->members()->syncWithoutDetaching(
                    collect($projectMemberIds)
                        ->mapWithKeys(fn (int $memberId): array => [
                            $memberId => [
                                'role' => User::ROLE_MEMBER,
                                'is_active' => fake()->boolean(90),
                            ],
                        ])
                        ->all()
                );

                $project->members()->syncWithoutDetaching([
                    $owner->id => [
                        'role' => User::ROLE_PROJECT_MANAGER,
                        'is_active' => true,
                    ],
                ]);

                /** @var Collection<int, User> $assignees */
                $assignees = $project->members()->get();

                $taskCount = fake()->numberBetween(6, 16);

                Task::withoutEvents(function () use ($taskCount, $project, $assignees, $monthStart, $monthEnd): void {
                    for ($position = 1; $position <= $taskCount; $position++) {
                        $taskStatus = fake()->randomElement([
                            Task::STATUS_TODO,
                            Task::STATUS_TODO,
                            Task::STATUS_DOING,
                            Task::STATUS_DOING,
                            Task::STATUS_DONE,
                            Task::STATUS_DONE,
                        ]);

                        $dueTaskDate = Carbon::instance(fake()->dateTimeBetween($project->start_date ?? '-1 month', $project->due_date ?? '+2 months'));
                        $completionStart = $project->start_date instanceof Carbon
                            ? $project->start_date->copy()
                            : now()->subMonth();
                        $completionEnd = now();

                        if ($completionStart->gt($completionEnd)) {
                            $completionStart = $completionEnd->copy()->subDays(7);
                        }

                        $completedTaskAt = $taskStatus === Task::STATUS_DONE
                            ? Carbon::instance(fake()->dateTimeBetween($completionStart, $completionEnd))
                            : null;

                        Task::factory()
                            ->for($project)
                            ->for($assignees->random(), 'assignee')
                            ->create([
                                'status' => $taskStatus,
                                'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
                                'due_date' => $dueTaskDate,
                                'completed_at' => $completedTaskAt,
                                'position' => $position,
                                'created_at' => Carbon::instance(fake()->dateTimeBetween($monthStart, $monthEnd)),
                            ]);
                    }
                });
            }
        }
    }
}
