<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= 'password', // cast 'hashed' lo encripta
            'role' => UserRole::EMPLOYEE->value,
            'business_id' => Business::factory(),
            'is_active' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::BUSINESS_ADMIN->value]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => ['role' => UserRole::SUPER_ADMIN->value, 'business_id' => null]);
    }
}
