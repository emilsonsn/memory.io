<?php

namespace App\Models;

use App\Models\Concerns\OwnedByAuthenticatedUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['memory_id', 'category_id'])]
#[Hidden(['user_id'])]
class Favorite extends Model
{
    use HasFactory, HasUuids, OwnedByAuthenticatedUser;

    protected static function booted(): void
    {
        static::creating(function (Favorite $favorite): void {
            $favorite->user_id ??= auth()->id();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function memory(): BelongsTo
    {
        return $this->belongsTo(Memory::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
