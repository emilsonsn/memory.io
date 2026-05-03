<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
    use HasFactory, HasUuids;

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
