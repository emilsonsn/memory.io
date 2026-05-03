<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Seed default development users.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@memory.io'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles([UserRole::ADMIN->value]);

        $basicPlan = Plan::where('name', 'Basic')->firstOrFail();
        $basicUser = User::updateOrCreate(
            ['email' => 'basic@memory.io'],
            [
                'name' => 'Basic User',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
        $basicUser->forceFill([
            'plan_id' => $basicPlan->id,
        ])->save();
        $basicUser->syncRoles([UserRole::USER->value]);

        $premiumPlan = Plan::where('name', 'Premium')->firstOrFail();
        $premiumUser = User::updateOrCreate(
            ['email' => 'premium@memory.io'],
            [
                'name' => 'Premium User',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
        $premiumUser->forceFill([
            'plan_id' => $premiumPlan->id,
        ])->save();
        $premiumUser->syncRoles([UserRole::USER->value]);
    }
}
