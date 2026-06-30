<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * Seed the default application plans.
     */
    public function run(): void
    {
        Plan::updateOrCreate(
            ['name' => 'Free'],
            [
                'description' => 'Free plan',
                'amount' => 0,
                'max_memories' => 1000,
                'max_categories' => 10,
                'can_export' => false,
                'can_use_ai' => false,
            ],
        );

        Plan::updateOrCreate(
            ['name' => 'Basic'],
            [
                'description' => 'Basic plan',
                'amount' => 29.90,
                'max_memories' => 5000,
                'max_categories' => 50,
                'can_export' => true,
                'can_use_ai' => false,
            ],
        );

        Plan::updateOrCreate(
            ['name' => 'Premium'],
            [
                'description' => 'Premium plan',
                'amount' => 99.90,
                'max_memories' => 1000,
                'max_categories' => 100,
                'can_export' => true,
                'can_use_ai' => true,
            ],
        );
    }
}
