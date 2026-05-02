<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'content', 'due_date'])]
class Memory extends Model
{
    use HasUuids, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope('owned_by_authenticated_user', function (Builder $builder): void {
            $userId = auth()->id();

            if ($userId === null) {
                $builder->whereNull($builder->qualifyColumn('user_id'));

                return;
            }

            $builder->where($builder->qualifyColumn('user_id'), $userId);
        });

        static::creating(function (Memory $memory): void {
            $memory->user_id ??= auth()->id();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withTimestamps();
    }
}
