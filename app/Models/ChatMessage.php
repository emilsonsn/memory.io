<?php

namespace App\Models;

use App\Models\Concerns\OwnedByAuthenticatedUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'chat_session_id',
    'role',
    'content',
    'sources',
    'prompt_tokens',
    'completion_tokens',
    'total_tokens',
])]
class ChatMessage extends Model
{
    use HasFactory, HasUuids, OwnedByAuthenticatedUser;

    protected static function booted(): void
    {
        static::creating(function (ChatMessage $message): void {
            $message->user_id ??= auth()->id();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sources' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
