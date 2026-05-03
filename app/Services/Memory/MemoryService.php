<?php

namespace App\Services\Memory;

use App\Models\Category;
use App\Models\Memory;
use App\Services\Concerns\AuditsActivities;
use App\Services\Plan\PlanLimitService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

class MemoryService
{
    use AuditsActivities;

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

        $memory = $this->object();

        $this->audit('memory.created', $memory, [
            'old' => null,
            'new' => $this->memoryAuditSnapshot($memory),
            'changed_fields' => ['title', 'content', 'due_date', 'category_ids'],
        ]);

        return $memory;
    }

    public function update(array $data): Memory
    {
        $this->verifyMemoryIsSet();

        $before = $this->memoryAuditSnapshot($this->memory);

        $categoryIds = $data['category_ids'] ?? null;
        unset($data['category_ids']);

        $this->memory->update($data);

        if ($categoryIds !== null) {
            $this->syncCategories($categoryIds);
        }

        $memory = $this->object();
        $after = $this->memoryAuditSnapshot($memory);

        $this->audit('memory.updated', $memory, [
            'old' => $before,
            'new' => $after,
            'changed_fields' => $this->resolveChangedFields($before, $after),
        ]);

        return $memory;
    }

    public function delete(): self
    {
        $this->verifyMemoryIsSet();

        $before = $this->memoryAuditSnapshot($this->memory);
        $memory = $this->memory;

        $this->memory->delete();

        $this->audit('memory.deleted', $memory, [
            'old' => $before,
            'new' => null,
            'changed_fields' => array_keys($before),
        ]);

        return $this;
    }

    public function getLogs(Memory $memory, int $perPage = 15): LengthAwarePaginator
    {
        return Activity::query()
            ->where('subject_type', Memory::class)
            ->where('subject_id', $memory->id)
            ->latest()
            ->paginate($perPage);
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

    /**
     * @return array<string, mixed>
     */
    private function memoryAuditSnapshot(Memory $memory): array
    {
        $dueDate = $memory->due_date;

        return [
            'title' => $memory->title,
            'content' => $memory->content,
            'due_date' => $dueDate?->toISOString(),
            'category_ids' => $memory->categories()
                ->pluck('categories.id')
                ->values()
                ->all(),
        ];
    }

}
