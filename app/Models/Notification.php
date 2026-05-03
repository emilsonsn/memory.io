<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Support\VersionedCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'url', 'type', 'seen'])]
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

        static::created(function (Notification $notification): void {
            VersionedCache::bump('notifications.list', $notification->user_id);
        });

        static::updated(function (Notification $notification): void {
            VersionedCache::bump('notifications.list', $notification->user_id);
        });

        static::deleted(function (Notification $notification): void {
            VersionedCache::bump('notifications.list', $notification->user_id);
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
            'type' => NotificationType::class,
            'seen' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
