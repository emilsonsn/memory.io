<?php

namespace Database\Factories;

use App\Models\Memory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Memory>
 */
class MemoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'content' => fake()->paragraph(),
            'color' => fake()->randomElement(['gray', 'red', 'orange', 'yellow', 'green', 'blue', 'purple', 'pink']),
            'due_date' => fake()->optional()->dateTimeBetween('+1 day', '+1 month'),
            'user_id' => User::factory(),
        ];
    }
}
