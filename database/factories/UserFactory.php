<?php

namespace Database\Factories;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
            'emp_code' => fake()->unique()->bothify('EMP-####'),
            'password' => static::$password ??= Hash::make('password'),
            'type' => UserType::User,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => UserType::Admin,
        ]);
    }

    public function viewer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => UserType::Viewer,
        ]);
    }
}
