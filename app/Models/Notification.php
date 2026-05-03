<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'url', 'type'])]
class Notification extends Model
{
    use HasUuids, HasFactory;

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

        static::creating(function (Notification $notification): void {
            $notification->user_id ??= auth()->id();
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
            'type' => 'string',
        ];
    }    

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
