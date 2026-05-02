<?php

namespace App\Services\Controller;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ControllerService
{
    public Category $category;

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function verifyCategorySet()
    {
        if (! isset($this->category)) {
            throw new ModelNotFoundException;
        }
    }

    public function object(): Category
    {
        return $this->category
            ->fresh();
    }

    public function getAllCategories($perPage = 15): LengthAwarePaginator
    {
        return Category::with('children')
            ->where('user_id', auth()->id())
            ->paginate($perPage);
    }

    public function getCategoryById($id): Category
    {
        $this->category = Category::where('user_id', auth()->id())
            ->findOrFail($id);

        return $this->object();
    }

    public function createCategory($data): self
    {
        $this->category = Category::create($data);

        return $this;
    }

    public function updateCategory($id, $data): self
    {
        $this->verifyCategorySet();

        $this->category->update($data);

        return $this;
    }

    public function deleteCategory($id): self
    {
        $this->verifyCategorySet();

        $this->category->delete();

        return $this;
    }
}
