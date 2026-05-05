<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'job_title' => fake()->jobTitle(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'bio' => fake()->sentence(18),
            'avatar_path' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_admin' => false,
            'role' => User::ROLE_MEMBER,
            'is_active' => true,
            'last_seen_at' => fake()->dateTimeBetween('-2 days', 'now'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function projectManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_PROJECT_MANAGER,
        ]);
    }
}
