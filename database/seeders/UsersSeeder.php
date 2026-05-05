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
        $defaultPassword = '@123Mudar';

        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@memory.io',
                'role' => UserRole::ADMIN,
            ],
            [
                'name' => 'Clara Costarc',
                'email' => 'claracostarc@gmail.com',
                'role' => UserRole::ADMIN,
            ],
            [
                'name' => 'Nathaliany Colly',
                'email' => 'contatonathalianycolly@gmail.com',
                'role' => UserRole::ADMIN,
            ],
            [
                'name' => 'Emilson',
                'email' => 'emilsonsn2@gmail.com',
                'role' => UserRole::ADMIN,
            ],
            [
                'name' => 'Let Moura',
                'email' => 'letmoura2017@gmail.com',
                'role' => UserRole::ADMIN,
            ],
            [
                'name' => 'Gabriel Souza',
                'email' => 'gabrielsndev@gmail.com',
                'role' => UserRole::ADMIN,
            ],
            [
                'name' => 'Basic User',
                'email' => 'basic@memory.io',
                'role' => UserRole::USER,
                'plan' => 'Basic',
            ],
            [
                'name' => 'Premium User',
                'email' => 'premium@memory.io',
                'role' => UserRole::USER,
                'plan' => 'Premium',
            ],
        ];

        $plansByName = Plan::query()
            ->whereIn('name', collect($users)->pluck('plan')->filter()->unique()->values())
            ->get()
            ->keyBy('name');

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                ],
            );

            if (isset($userData['plan'])) {
                $plan = $plansByName->get($userData['plan']);

                if (! $plan) {
                    $plan = Plan::where('name', $userData['plan'])->firstOrFail();
                }

                $user->forceFill([
                    'plan_id' => $plan->id,
                ])->save();
            }

            $user->syncRoles([$userData['role']->value]);
        }
    }
}
