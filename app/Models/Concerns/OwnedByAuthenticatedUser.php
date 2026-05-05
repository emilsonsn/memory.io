<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait OwnedByAuthenticatedUser
{
    protected static function bootOwnedByAuthenticatedUser(): void
    {
        static::addGlobalScope('owned_by_authenticated_user', function (Builder $builder): void {
            $userId = auth()->id();

            if ($userId === null) {
                $builder->whereNull($builder->qualifyColumn('user_id'));

                return;
            }

            $builder->where($builder->qualifyColumn('user_id'), $userId);
        });
    }
}
