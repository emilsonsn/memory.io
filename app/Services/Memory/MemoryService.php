<?php

namespace App\Services\Memory;

use App\Models\Category;
use App\Models\Memory;
use App\Services\Plan\PlanLimitService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class MemoryService
{
    private Memory $memory;

    public function __construct(private readonly PlanLimitService $planLimitService) {}

    public function setMemory(Memory $memory): self
    {
        $this->memory = $memory;

        return $this;
    }

    public function object(): Memory
    {
        return $this->memory
            ->fresh()
            ->load([
                'categories',
            ]);
    }

    public function verifyMemoryIsSet(): void
    {
        if (! isset($this->memory)) {
            throw new RuntimeException('Memory is not set.');
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getAll(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $categoryIds = $filters['category_ids'] ?? [];

        $query = Memory::query()
            ->with('categories');

        $query->when(! empty($filters['text']), function ($query) use ($filters) {
            $searchText = trim((string) $filters['text']);

            $query->where(function ($innerQuery) use ($searchText): void {
                $innerQuery
                    ->where('title', 'like', "%{$searchText}%")
                    ->orWhere('content', 'like', "%{$searchText}%");
            });
        })->when(! empty($filters['created_from']), function ($query) use ($filters) {
            $query->whereDate('created_at', '>=', (string) $filters['created_from']);
        })->when(! empty($filters['created_to']), function ($query) use ($filters) {
            $query->whereDate('created_at', '<=', (string) $filters['created_to']);
        })->when(! empty($filters['updated_from']), function ($query) use ($filters) {
            $query->whereDate('updated_at', '>=', (string) $filters['updated_from']);
        })->when(! empty($filters['updated_to']), function ($query) use ($filters) {
            $query->whereDate('updated_at', '<=', (string) $filters['updated_to']);
        })->when(! empty($filters['due_from']), function ($query) use ($filters) {
            $query->whereDate('due_date', '>=', (string) $filters['due_from']);
        })->when(! empty($filters['due_to']), function ($query) use ($filters) {
            $query->whereDate('due_date', '<=', (string) $filters['due_to']);
        })->when(is_array($categoryIds) && $categoryIds !== [], function ($query) use ($categoryIds) {
            $query->whereHas('categories', function ($categoriesQuery) use ($categoryIds): void {
                $categoriesQuery->whereIn('categories.id', $categoryIds);
            });
        });

        return $query
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Memory
    {
        $this->planLimitService->ensureCanCreateMemory(auth()->user());

        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $this->memory = Memory::create($data);
        $this->syncCategories($categoryIds);

        return $this->object();
    }

    public function update(array $data): Memory
    {
        $this->verifyMemoryIsSet();

        $categoryIds = $data['category_ids'] ?? null;
        unset($data['category_ids']);

        $this->memory->update($data);

        if ($categoryIds !== null) {
            $this->syncCategories($categoryIds);
        }

        return $this->object();
    }

    public function delete(): self
    {
        $this->verifyMemoryIsSet();
        $this->memory->delete();

        return $this;
    }

    /**
     * @param  array<int, string>  $categoryIds
     */
    private function syncCategories(array $categoryIds): void
    {
        $ownedCategoryIds = Category::query()
            ->whereIn('id', $categoryIds)
            ->pluck('id')
            ->all();

        $this->memory->categories()->sync($ownedCategoryIds);
    }
}
