<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 0, 999.99),
            'max_memories' => null,
            'max_categories' => null,
            'can_export' => false,
            'can_use_ai' => false,
        ];
    }
}
