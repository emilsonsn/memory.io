<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Models\Concerns\OwnedByAuthenticatedUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'url', 'type', 'seen'])]
class Notification extends Model
{
    use HasFactory, HasUuids, OwnedByAuthenticatedUser;

    protected static function booted(): void
    {
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
            'type' => NotificationType::class,
            'seen' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
