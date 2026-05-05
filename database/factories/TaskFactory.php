<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'assigned_to' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->boolean(75) ? fake()->paragraph() : null,
            'status' => fake()->randomElement([Task::STATUS_TODO, Task::STATUS_DOING, Task::STATUS_DONE]),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'estimated_hours' => fake()->numberBetween(2, 40),
            'due_date' => fake()->dateTimeBetween('-2 months', '+3 months'),
            'completed_at' => null,
            'position' => fake()->numberBetween(1, 20),
        ];
    }
}
