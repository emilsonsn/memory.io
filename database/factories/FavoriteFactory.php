<?php

namespace Database\Factories;

use App\Models\Favorite;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Favorite>
 */
class FavoriteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'memory_id' => Memory::factory(),
            'category_id' => null,
        ];
    }
}
