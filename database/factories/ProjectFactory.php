<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-5 months', '+2 months');
        $dueDate = fake()->boolean(80)
            ? fake()->dateTimeBetween($startDate, '+4 months')
            : null;

        return [
            'owner_id' => User::factory()->projectManager(),
            'client_id' => null,
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'objective' => fake()->sentence(10),
            'status' => fake()->randomElement(['planning', 'active', 'on_hold', 'completed', 'cancelled']),
            'is_template' => false,
            'template_name' => null,
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'budget' => fake()->randomFloat(2, 5000, 150000),
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'is_archived' => false,
            'archived_at' => null,
            'completed_at' => null,
        ];
    }
}
