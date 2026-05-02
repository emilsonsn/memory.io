<?php

namespace App\Services\Memory;

use App\Models\Category;
use App\Models\Memory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class MemoryService
{
    private Memory $memory;

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

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return Memory::query()
            ->with('categories')
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Memory
    {
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
