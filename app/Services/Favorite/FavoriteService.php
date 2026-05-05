<?php

namespace App\Services\Favorite;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Memory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class FavoriteService
{
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return Favorite::query()
            ->with(['memory.categories', 'category.parent'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @param  array{memory_id?: string|null, category_id?: string|null}  $data
     */
    public function add(array $data): Favorite
    {
        $this->ensureExactlyOneTarget($data);
        $this->ensureTargetIsOwned($data);

        $favorite = Favorite::query()->firstOrCreate([
            'memory_id' => $data['memory_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
        ]);

        return $favorite->fresh()->load(['memory.categories', 'category.parent']);
    }

    /**
     * @param  array{memory_id?: string|null, category_id?: string|null}  $data
     */
    public function remove(array $data): bool
    {
        $this->ensureExactlyOneTarget($data);
        $this->ensureTargetIsOwned($data);

        return (bool) Favorite::query()
            ->where('memory_id', $data['memory_id'] ?? null)
            ->where('category_id', $data['category_id'] ?? null)
            ->delete();
    }

    /**
     * @param  array{memory_id?: string|null, category_id?: string|null}  $data
     */
    private function ensureExactlyOneTarget(array $data): void
    {
        $hasMemory = ! empty($data['memory_id']);
        $hasCategory = ! empty($data['category_id']);

        if ($hasMemory === $hasCategory) {
            throw new InvalidArgumentException('Favorite must reference exactly one memory or category.');
        }
    }

    /**
     * @param  array{memory_id?: string|null, category_id?: string|null}  $data
     */
    private function ensureTargetIsOwned(array $data): void
    {
        if (! empty($data['memory_id']) && ! Memory::query()->whereKey($data['memory_id'])->exists()) {
            throw new InvalidArgumentException('Memory is not available for favorite.');
        }

        if (! empty($data['category_id']) && ! Category::query()->whereKey($data['category_id'])->exists()) {
            throw new InvalidArgumentException('Category is not available for favorite.');
        }
    }
}
