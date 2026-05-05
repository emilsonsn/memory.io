<?php

namespace App\Models;

use App\Models\Concerns\OwnedByAuthenticatedUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'last_message_at'])]
class ChatSession extends Model
{
    use HasFactory, HasUuids, OwnedByAuthenticatedUser;

    protected static function booted(): void
    {
        static::creating(function (ChatSession $session): void {
            $session->user_id ??= auth()->id();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
