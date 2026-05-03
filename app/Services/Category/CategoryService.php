<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Services\Concerns\AuditsActivities;
use App\Services\Plan\PlanLimitService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class CategoryService
{
    use AuditsActivities;

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

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getAll(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Category::query()
            ->with('parent');

        $query->when(! empty($filters['color']), function ($query) use ($filters) {
            $query->where('color', (string) $filters['color']);
        });

        return $query
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Category
    {
        $this->planLimitService->ensureCanCreateCategory(auth()->user());

        $this->category = Category::create($data);

        $category = $this->object();

        $this->audit('category.created', $category, [
            'old' => null,
            'new' => $this->categoryAuditSnapshot($category),
            'changed_fields' => ['label', 'description', 'color', 'parent_id'],
        ]);

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
            'color' => $category->color?->value,
            'parent_id' => $category->parent_id,
        ];
    }
}
