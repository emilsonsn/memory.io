<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\Controller\ControllerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private readonly ControllerService $controllerService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));
        $categories = $this->controllerService->getAllCategories($perPage);

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
        $category = $this->controllerService->getCategoryById($category);

        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully.',
            'data' => CategoryResource::make($category),
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->controllerService
            ->createCategory($request->validated())
            ->object();

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => CategoryResource::make($category),
        ], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $updatedCategory = $this->controllerService
            ->setCategory($category)
            ->updateCategory($category, $request->validated())
            ->object();

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => CategoryResource::make($updatedCategory),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->controllerService
            ->setCategory($category)
            ->deleteCategory($category);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
            'data' => null,
        ]);
    }
}
