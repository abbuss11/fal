<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskSubtask;
use App\Models\Team;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class WorkspaceRealCaseSeeder extends Seeder
{
    /**
     * Seed real-case workspace data while preserving real users.
     */
    public function run(): void
    {
        $this->purgeWorkspaceData();
        $this->pruneObviousTestUsers();

        $users = $this->activeUsers();
        if ($users->isEmpty()) {
            $users = new Collection([$this->createFallbackAdmin()]);
        }

        $clients = $this->seedClients($users);
        $teams = $this->seedTeams($users);
        $this->seedProjects($users, $clients, $teams);
    }

    private function purgeWorkspaceData(): void
    {
        $tables = [
            'comment_mentions',
            'notifications',
            'activity_logs',
            'task_dependencies',
            'task_tag',
            'task_tags',
            'task_subtasks',
            'task_comments',
            'timesheets',
            'project_messages',
            'project_files',
            'tasks',
            'project_user',
            'team_user',
            'projects',
            'teams',
            'clients',
        ];

        Schema::disableForeignKeyConstraints();
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
        Schema::enableForeignKeyConstraints();
    }

    private function pruneObviousTestUsers(): void
    {
        $testUserIds = $this->testUsersQuery()->pluck('id');
        if ($testUserIds->isEmpty()) {
            return;
        }

        $remainingRealUsers = User::query()
            ->whereNotIn('id', $testUserIds->all())
            ->count();

        if ($remainingRealUsers === 0) {
            return;
        }

        User::query()
            ->whereIn('id', $testUserIds->all())
            ->delete();
    }

    private function activeUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    private function createFallbackAdmin(): User
    {
        return User::query()->create([
            'name' => 'Administrateur PMS',
            'email' => 'admin@fal.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => User::ROLE_ADMIN,
            'is_admin' => true,
            'is_active' => true,
            'notify_email' => false,
            'notify_realtime' => true,
            'notify_push' => false,
            'last_seen_at' => now(),
        ]);
    }

    private function seedClients(Collection $users): Collection
    {
        $clients = collect();
        $clientUsers = $users
            ->where('role', User::ROLE_CLIENT)
            ->values();

        foreach ($clientUsers as $clientUser) {
            $clients->push(Client::query()->create([
                'name' => $clientUser->name,
                'company' => 'Client '.mb_strtoupper((string) mb_substr($clientUser->name, 0, 3)),
                'email' => $clientUser->email,
                'phone' => $clientUser->phone,
                'notes' => 'Compte client actif dans le portefeuille projet.',
                'is_active' => true,
            ]));
        }

        $templates = [
            ['name' => 'Direction Financiere', 'company' => 'FAL Group', 'email' => 'finance@fal-group.local', 'phone' => '+227 90 00 00 01'],
            ['name' => 'Direction Operations', 'company' => 'FAL Group', 'email' => 'ops@fal-group.local', 'phone' => '+227 90 00 00 02'],
            ['name' => 'Service Support', 'company' => 'FAL Services', 'email' => 'support@fal-services.local', 'phone' => '+227 90 00 00 03'],
            ['name' => 'Programme Digital', 'company' => 'FAL Digital', 'email' => 'digital@fal.local', 'phone' => '+227 90 00 00 04'],
        ];

        foreach ($templates as $template) {
            if ($clients->count() >= 4) {
                break;
            }

            $clients->push(Client::query()->create([
                'name' => $template['name'],
                'company' => $template['company'],
                'email' => $template['email'],
                'phone' => $template['phone'],
                'notes' => 'Partenaire de reference pour les projets internes.',
                'is_active' => true,
            ]));
        }

        return $clients->values();
    }

    private function seedTeams(Collection $users): Collection
    {
        $leadUsers = $users
            ->filter(fn (User $user): bool => in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_PROJECT_MANAGER], true))
            ->values();
        $workforce = $users
            ->filter(fn (User $user): bool => $user->role !== User::ROLE_CLIENT)
            ->values();

        if ($leadUsers->isEmpty()) {
            $leadUsers = $workforce->take(1)->values();
        }

        $teamTemplates = [
            ['name' => 'Equipe Produit', 'description' => 'Pilotage roadmap, priorisation backlog et qualite fonctionnelle.'],
            ['name' => 'Equipe Delivery', 'description' => 'Execution des developpements, tests et mise en production.'],
            ['name' => 'Equipe Support', 'description' => 'Support utilisateur, correction incidents et documentation.'],
        ];

        $teams = collect();
        $cursor = 0;
        foreach ($teamTemplates as $index => $template) {
            if ($leadUsers->isEmpty()) {
                break;
            }

            /** @var User $owner */
            $owner = $leadUsers[$index % $leadUsers->count()];
            $team = Team::query()->create([
                'owner_id' => $owner->id,
                'name' => $template['name'],
                'description' => $template['description'],
                'is_active' => true,
            ]);

            $members = collect([$owner]);
            $batchSize = min(max($workforce->count(), 1), 5);
            for ($offset = 0; $offset < $batchSize; $offset++) {
                if ($workforce->isEmpty()) {
                    break;
                }

                $members->push($workforce[($cursor + $offset) % $workforce->count()]);
            }
            $cursor += max($batchSize - 1, 1);

            $team->members()->sync(
                $members
                    ->unique('id')
                    ->mapWithKeys(function (User $member) use ($owner): array {
                        return [
                            $member->id => [
                                'role' => $member->id === $owner->id
                                    ? User::ROLE_PROJECT_MANAGER
                                    : $this->normalizePivotRole($member->role),
                                'is_active' => true,
                            ],
                        ];
                    })
                    ->all()
            );

            $teams->push($team);
        }

        return $teams->values();
    }

    private function seedProjects(Collection $users, Collection $clients, Collection $teams): void
    {
        $leadUsers = $users
            ->filter(fn (User $user): bool => in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_PROJECT_MANAGER], true))
            ->values();
        $workforce = $users
            ->filter(fn (User $user): bool => $user->role !== User::ROLE_CLIENT)
            ->values();

        if ($leadUsers->isEmpty()) {
            $leadUsers = $workforce->take(1)->values();
        }

        $projectTemplates = [
            [
                'name' => 'Refonte Portail Client',
                'description' => 'Modernisation de l espace client pour suivi des demandes et documents.',
                'objective' => 'Reduire de 30% le volume de demandes manuelles via un espace self-service.',
                'status' => Project::STATUS_ACTIVE,
                'priority' => Project::PRIORITY_HIGH,
                'budget' => 24000000,
                'start_offset' => -45,
                'due_offset' => 35,
                'tasks' => [
                    ['title' => 'Cadrage des parcours utilisateurs', 'lane' => 'done', 'priority' => Task::PRIORITY_HIGH, 'estimated_hours' => 12, 'due_offset' => -30],
                    ['title' => 'Design des ecrans principaux', 'lane' => 'review', 'priority' => Task::PRIORITY_MEDIUM, 'estimated_hours' => 18, 'due_offset' => -4],
                    ['title' => 'Implementation du module tickets', 'lane' => 'doing', 'priority' => Task::PRIORITY_HIGH, 'estimated_hours' => 26, 'due_offset' => 6],
                    ['title' => 'Integration authentification SSO', 'lane' => 'todo', 'priority' => Task::PRIORITY_URGENT, 'estimated_hours' => 20, 'due_offset' => 9],
                    ['title' => 'Plan de recette metier', 'lane' => 'todo', 'priority' => Task::PRIORITY_MEDIUM, 'estimated_hours' => 10, 'due_offset' => 12],
                ],
            ],
            [
                'name' => 'Automatisation Reporting RH',
                'description' => 'Consolidation automatique des indicateurs RH mensuels.',
                'objective' => 'Publier les reportings hebdomadaires sans ressaisie manuelle.',
                'status' => Project::STATUS_ACTIVE,
                'priority' => Project::PRIORITY_MEDIUM,
                'budget' => 15500000,
                'start_offset' => -20,
                'due_offset' => 55,
                'tasks' => [
                    ['title' => 'Cartographie des sources de donnees', 'lane' => 'done', 'priority' => Task::PRIORITY_MEDIUM, 'estimated_hours' => 8, 'due_offset' => -14],
                    ['title' => 'Creation pipeline ETL', 'lane' => 'doing', 'priority' => Task::PRIORITY_HIGH, 'estimated_hours' => 22, 'due_offset' => 10],
                    ['title' => 'Tableau de bord absenteisme', 'lane' => 'review', 'priority' => Task::PRIORITY_HIGH, 'estimated_hours' => 14, 'due_offset' => 4],
                    ['title' => 'Validation DG et publication', 'lane' => 'todo', 'priority' => Task::PRIORITY_MEDIUM, 'estimated_hours' => 6, 'due_offset' => 16],
                ],
            ],
            [
                'name' => 'Programme Mobile Terrain',
                'description' => 'Application mobile pour collecte terrain et synchronisation centrale.',
                'objective' => 'Garantir la remontee des donnees terrain en moins de 5 minutes.',
                'status' => Project::STATUS_PLANNING,
                'priority' => Project::PRIORITY_HIGH,
                'budget' => 31000000,
                'start_offset' => -5,
                'due_offset' => 90,
                'tasks' => [
                    ['title' => 'Atelier besoins equipes terrain', 'lane' => 'doing', 'priority' => Task::PRIORITY_MEDIUM, 'estimated_hours' => 10, 'due_offset' => 7],
                    ['title' => 'Architecture offline-first', 'lane' => 'todo', 'priority' => Task::PRIORITY_HIGH, 'estimated_hours' => 20, 'due_offset' => 18],
                    ['title' => 'Prototype synchronisation', 'lane' => 'todo', 'priority' => Task::PRIORITY_URGENT, 'estimated_hours' => 24, 'due_offset' => 24],
                ],
            ],
        ];

        foreach ($projectTemplates as $index => $template) {
            if ($leadUsers->isEmpty()) {
                break;
            }

            /** @var User $owner */
            $owner = $leadUsers[$index % $leadUsers->count()];
            $startDate = Carbon::today()->addDays((int) $template['start_offset']);
            $dueDate = Carbon::today()->addDays((int) $template['due_offset']);

            $project = Project::withoutEvents(function () use ($template, $owner, $clients, $index, $startDate, $dueDate): Project {
                return Project::query()->create([
                    'owner_id' => $owner->id,
                    'client_id' => $clients->isNotEmpty() ? $clients[$index % $clients->count()]->id : null,
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'objective' => $template['objective'],
                    'status' => $template['status'],
                    'priority' => $template['priority'],
                    'budget' => $template['budget'],
                    'start_date' => $startDate,
                    'due_date' => $dueDate,
                    'is_template' => false,
                    'template_name' => null,
                    'is_archived' => false,
                    'archived_at' => null,
                    'completed_at' => $template['status'] === Project::STATUS_COMPLETED ? now() : null,
                ]);
            });

            $memberCandidates = $this->projectMembersFromTeam($teams, $index, $workforce);
            $memberPayload = $memberCandidates
                ->push($owner)
                ->unique('id')
                ->mapWithKeys(function (User $member) use ($owner): array {
                    return [
                        $member->id => [
                            'role' => $member->id === $owner->id
                                ? User::ROLE_PROJECT_MANAGER
                                : $this->normalizePivotRole($member->role),
                            'is_active' => true,
                        ],
                    ];
                })
                ->all();

            $project->members()->sync($memberPayload);

            $assignees = $memberCandidates->isNotEmpty()
                ? $memberCandidates->values()
                : collect([$owner]);
            $lanePositions = [
                'todo' => 0,
                'doing' => 0,
                'review' => 0,
                'done' => 0,
            ];

            $createdTasks = Task::withoutEvents(function () use ($template, $project, $assignees, $startDate, &$lanePositions): Collection {
                $tasks = collect();
                foreach ($template['tasks'] as $taskIndex => $taskTemplate) {
                    $lane = (string) $taskTemplate['lane'];
                    $lanePositions[$lane] = ($lanePositions[$lane] ?? 0) + 1;
                    $status = $lane === 'review' ? Task::STATUS_DOING : $lane;
                    $isInReview = $lane === 'review';
                    $completedAt = $status === Task::STATUS_DONE
                        ? Carbon::today()->subDays(max(1, 7 - $taskIndex))
                        : null;

                    /** @var User $assignee */
                    $assignee = $assignees[$taskIndex % $assignees->count()];
                    $task = Task::query()->create([
                        'project_id' => $project->id,
                        'assigned_to' => $assignee->id,
                        'title' => $taskTemplate['title'],
                        'description' => 'Execution operationnelle: '.$taskTemplate['title'],
                        'status' => $status,
                        'is_in_review' => $isInReview,
                        'priority' => $taskTemplate['priority'],
                        'estimated_hours' => (int) $taskTemplate['estimated_hours'],
                        'due_date' => $startDate->copy()->addDays((int) $taskTemplate['due_offset']),
                        'completed_at' => $completedAt,
                        'position' => (int) $lanePositions[$lane],
                    ]);

                    $tasks->push($task);
                }

                return $tasks;
            });

            $firstTask = $createdTasks->first();
            if ($firstTask instanceof Task) {
                TaskSubtask::query()->create([
                    'task_id' => $firstTask->id,
                    'title' => 'Valider les prerequis metiers',
                    'is_completed' => true,
                    'completed_by' => $owner->id,
                    'completed_at' => now()->subDays(2),
                    'position' => 1,
                ]);
                TaskSubtask::query()->create([
                    'task_id' => $firstTask->id,
                    'title' => 'Partager le compte-rendu equipe',
                    'is_completed' => false,
                    'completed_by' => null,
                    'completed_at' => null,
                    'position' => 2,
                ]);
            }

            $timesheetTasks = $createdTasks->take(2)->values();
            foreach ($timesheetTasks as $timesheetIndex => $task) {
                /** @var User $assignee */
                $assignee = $assignees[$timesheetIndex % $assignees->count()];
                Timesheet::query()->create([
                    'user_id' => $assignee->id,
                    'project_id' => $project->id,
                    'task_id' => $task->id,
                    'work_date' => Carbon::today()->subDays($timesheetIndex + 1),
                    'hours' => 3.5 + $timesheetIndex,
                    'note' => 'Execution sur '.$task->title,
                ]);
            }
        }
    }

    private function projectMembersFromTeam(Collection $teams, int $projectIndex, Collection $workforce): Collection
    {
        if ($teams->isNotEmpty()) {
            /** @var Team $team */
            $team = $teams[$projectIndex % $teams->count()];
            $team->loadMissing('members');

            return $team->members
                ->filter(fn (User $user): bool => $user->role !== User::ROLE_CLIENT)
                ->take(5)
                ->values();
        }

        return $workforce->take(5)->values();
    }

    private function normalizePivotRole(string $role): string
    {
        return match ($role) {
            User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER => User::ROLE_PROJECT_MANAGER,
            User::ROLE_MANAGER => User::ROLE_MANAGER,
            User::ROLE_CLIENT => User::ROLE_CLIENT,
            default => User::ROLE_MEMBER,
        };
    }

    private function testUsersQuery()
    {
        $domains = ['fal-pms.test', 'example.com', 'example.net', 'example.org'];

        return User::query()
            ->where(function ($query) use ($domains): void {
                foreach ($domains as $domain) {
                    $query->orWhere('email', 'like', '%@'.$domain);
                }
            });
    }
}
