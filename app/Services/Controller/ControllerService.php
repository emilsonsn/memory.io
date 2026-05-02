<?php

namespace App\Services\Controller;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ControllerService
{
    public function getAllCategories(int $perPage = 15): LengthAwarePaginator
    {
        return Category::query()
            ->with('parent')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function getCategoryById(string $id): Category
    {
        return Category::query()
            ->with(['parent', 'children'])
            ->findOrFail($id);
    }

    public function createCategory(array $data): Category
    {
        return Category::create($data)->load(['parent', 'children']);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function updateCategory(string $id, array $data): Category
    {
        $category = $this->getCategoryById($id);

        $category->update($data);

        return $category->refresh()->load(['parent', 'children']);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function deleteCategory(string $id): void
    {
        $this->getCategoryById($id)->delete();
    }
}
