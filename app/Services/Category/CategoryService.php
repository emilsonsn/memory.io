<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Services\Concerns\AuditsActivities;
use App\Services\Plan\PlanLimitService;
use App\Support\VersionedCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class CategoryService
{
    use AuditsActivities;

    private const LIST_CACHE_TTL_SECONDS = 300;

    private Category $category;

    public function __construct(private readonly PlanLimitService $planLimitService) {}

    public function setCategory(Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function object(): Category
    {
        return $this->category
            ->fresh()
            ->load([
                'parent',
                'children',
            ]);
    }

    public function verifyCategoryIsSet(): void
    {
        if (! isset($this->category)) {
            throw new RuntimeException('Category is not set.');
        }
    }

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        $userId = auth()->id();
        $page = request()->integer('page', 1);

        return VersionedCache::remember(
            namespace: 'categories.list',
            params: [
                'per_page' => $perPage,
                'page' => $page,
            ],
            ttlSeconds: self::LIST_CACHE_TTL_SECONDS,
            callback: static fn () => Category::query()
                ->with('parent')
                ->latest()
                ->paginate($perPage),
            scope: $userId,
        );
    }

    public function create(array $data): Category
    {
        $this->planLimitService->ensureCanCreateCategory(auth()->user());

        $this->category = Category::create($data);

        $category = $this->object();

        $this->audit('category.created', $category, [
            'old' => null,
            'new' => $this->categoryAuditSnapshot($category),
            'changed_fields' => ['label', 'description', 'parent_id'],
        ]);

        VersionedCache::bump('categories.list', $category->user_id);

        return $category;
    }

    public function update(array $data): Category
    {
        $this->verifyCategoryIsSet();

        $before = $this->categoryAuditSnapshot($this->category);

        $this->category->update($data);

        $category = $this->object();
        $after = $this->categoryAuditSnapshot($category);

        $this->audit('category.updated', $category, [
            'old' => $before,
            'new' => $after,
            'changed_fields' => $this->resolveChangedFields($before, $after),
        ]);

        VersionedCache::bump('categories.list', $category->user_id);

        return $category;
    }

    public function delete(): self
    {
        $this->verifyCategoryIsSet();

        $before = $this->categoryAuditSnapshot($this->category);
        $category = $this->category;

        $this->category->delete();

        $this->audit('category.deleted', $category, [
            'old' => $before,
            'new' => null,
            'changed_fields' => array_keys($before),
        ]);

        VersionedCache::bump('categories.list', $category->user_id);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryAuditSnapshot(Category $category): array
    {
        return [
            'label' => $category->label,
            'description' => $category->description,
            'parent_id' => $category->parent_id,
        ];
    }

}
