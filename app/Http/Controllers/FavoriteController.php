<?php

namespace App\Http\Controllers;

use App\Http\Requests\Favorite\DeleteFavoriteRequest;
use App\Http\Requests\Favorite\IndexFavoriteRequest;
use App\Http\Requests\Favorite\StoreFavoriteRequest;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use App\Services\Favorite\FavoriteService;
use Illuminate\Http\JsonResponse;

class FavoriteController extends Controller
{
    public function __construct(private readonly FavoriteService $favoriteService) {}

    public function index(IndexFavoriteRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Favorite::class);

        $favorites = $this->favoriteService->getAll((int) $request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Favorites retrieved successfully.',
            'data' => FavoriteResource::collection($favorites),
            'meta' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
            ],
        ]);
    }

    public function store(StoreFavoriteRequest $request): JsonResponse
    {
        $this->authorize('create', Favorite::class);

        $favorite = $this->favoriteService->add($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Favorite added successfully.',
            'data' => FavoriteResource::make($favorite),
        ], 201);
    }

    public function destroy(DeleteFavoriteRequest $request): JsonResponse
    {
        $this->favoriteService->remove($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Favorite removed successfully.',
            'data' => null,
        ]);
    }
}
