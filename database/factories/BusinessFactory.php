<?php

namespace Database\Factories;

use App\Enums\BusinessPlan;
use App\Enums\BusinessStatus;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 999999),
            'status' => BusinessStatus::ACTIVE->value,
            'plan' => BusinessPlan::FREE->value,
            'tax_rate' => 16.00,
        ];
    }
}
