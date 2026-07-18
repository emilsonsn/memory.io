<?php

namespace App\Models;

use App\Enums\NoteColor;
use App\Models\Concerns\OwnedByAuthenticatedUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['label', 'description', 'color', 'parent_id'])]
#[Hidden(['user_id'])]
class Category extends Model
{
    use HasFactory, HasUuids, OwnedByAuthenticatedUser;

    protected static function booted(): void
    {
        static::creating(function (Category $category): void {
            $category->user_id ??= auth()->id();
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function memories(): HasMany
    {
        return $this->hasMany(Memory::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'color' => NoteColor::class,
        ];
    }
}
