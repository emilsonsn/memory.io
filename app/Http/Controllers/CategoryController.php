<?php

namespace App\Http\Controllers;

use App\Events\CategoryExportRequested;
use App\Http\Requests\Category\IndexCategoryRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\Category\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(IndexCategoryRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $filters = $request->validated();
        $perPage = $filters['per_page'] ?? 15;
        unset($filters['per_page']);

        $categories = $this->categoryService->getAll($perPage, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully.',
            'data' => CategoryResource::collection($categories),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        $category = $this->categoryService
            ->setCategory($category)
            ->object();

        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully.',
            'data' => CategoryResource::make($category),
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = $this->categoryService
            ->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => CategoryResource::make($category),
        ], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $updatedCategory = $this->categoryService
            ->setCategory($category)
            ->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => CategoryResource::make($updatedCategory),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $this->categoryService
            ->setCategory($category)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
            'data' => null,
        ]);
    }

    public function export(Category $category): JsonResponse
    {
        $this->authorize('export', $category);

        event(new CategoryExportRequested(
            categoryId: (string) $category->id,
            userId: (string) auth()->id(),
        ));

        return response()->json([
            'success' => true,
            'message' => 'Category export started. You will be notified when it is ready.',
            'data' => null,
        ], 202);
    }
}
