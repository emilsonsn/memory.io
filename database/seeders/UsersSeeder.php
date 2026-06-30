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
                'role' => UserRole::USER,
                'plan' => 'Premium',
            ],
            [
                'name' => 'Nathaliany Colly',
                'email' => 'contatonathalianycolly@gmail.com',
                'role' => UserRole::USER,
                'plan' => 'Premium',
            ],
            [
                'name' => 'Emilson',
                'email' => 'emilsonsn2@gmail.com',
                'role' => UserRole::USER,
                'plan' => 'Premium',
            ],
            [
                'name' => 'Let Moura',
                'email' => 'letmoura2017@gmail.com',
                'role' => UserRole::USER,
            ],
            [
                'name' => 'Gabriel Souza',
                'email' => 'gabrielsndev@gmail.com',
                'role' => UserRole::USER,
                
                'plan' => 'Premium',
            ],
        ];

        $planNames = collect($users)
            ->pluck('plan')
            ->filter()
            ->push('Free')
            ->unique()
            ->values();

        $plansByName = Plan::query()
            ->whereIn('name', $planNames)
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

            $planName = $userData['plan'] ?? null;

            if ($planName === null && $userData['role'] === UserRole::USER) {
                $planName = 'Free';
            }

            if ($planName !== null) {
                $plan = $plansByName->get($planName);

                if (! $plan) {
                    $plan = Plan::where('name', $planName)->firstOrFail();
                }

                $user->forceFill([
                    'plan_id' => $plan->id,
                ])->save();
            }

            $user->syncRoles([$userData['role']->value]);
        }
    }
}
