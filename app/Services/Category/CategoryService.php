<?php

namespace App\Services\Category;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryService
{
    private Category $category;

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
                'children'
            ]);
    }    

    public function verifyCategoryIsSet(): void
    {
        if (! isset($this->category)) {
            throw new \RuntimeException('Category is not set.');
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
        $this->category = Category::create($data);
        return $this->object();
    }

    /**
     * @throws ModelNotFoundException
     */
    public function update(array $data): Category
    {
        $this->verifyCategoryIsSet();

        $this->category->update($data);

        return $this->object();
    }

    /**
     * @throws ModelNotFoundException
     */
    public function delete(): self
    {
        $this->verifyCategoryIsSet();
        $this->category->delete();
        return $this;
    }
}
