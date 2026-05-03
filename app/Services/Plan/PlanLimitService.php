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

        $limit = $user->plan?->max_memories;

        if ($limit === null) {
            throw new PlanLimitExceededException('A plan is required to create memories.');
        }

        if ($user->memories()->count() >= $limit) {
            throw new PlanLimitExceededException("Your current plan allows up to {$limit} memories.");
        }
    }

    public function ensureCanCreateCategory(User $user): void
    {
        if ($user->hasRole('admin')) {
            return;
        }

        $limit = $user->plan?->max_categories;

        if ($limit === null) {
            throw new PlanLimitExceededException('A plan is required to create categories.');
        }

        if ($user->categories()->count() >= $limit) {
            throw new PlanLimitExceededException("Your current plan allows up to {$limit} categories.");
        }
    }

    public function canExport(User $user): bool
    {
        return $user->hasRole('admin') || (bool) $user->plan?->can_export;
    }

    public function canUseAi(User $user): bool
    {
        return $user->hasRole('admin') || (bool) $user->plan?->can_use_ai;
    }
}
