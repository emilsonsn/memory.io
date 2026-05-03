<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Services\Plan\PlanLimitService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class CategoryService
{
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
        return Category::query()
            ->with('parent')
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Category
    {
        $this->planLimitService->ensureCanCreateCategory(auth()->user());

        $this->category = Category::create($data);

        return $this->object();
    }

    public function update(array $data): Category
    {
        $this->verifyCategoryIsSet();

        $this->category->update($data);

        return $this->object();
    }

    public function delete(): self
    {
        $this->verifyCategoryIsSet();
        $this->category->delete();

        return $this;
    }
}
