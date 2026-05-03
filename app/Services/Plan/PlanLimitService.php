<?php

namespace App\Services\Plan;

use App\Exceptions\PlanLimitExceededException;
use App\Models\User;

class PlanLimitService
{
    public function ensureCanCreateMemory(User $user): void
    {
        if ($user->hasRole('admin')) {
            return;
        }

        $limit = $user->plan?->memory_limit;

        if ($limit === null) {
            throw new PlanLimitExceededException('A plan is required to create memories.');
        }

        if ($user->memories()->count() >= $limit) {
            throw new PlanLimitExceededException("Your current plan allows up to {$limit} memories.");
        }
    }
}
