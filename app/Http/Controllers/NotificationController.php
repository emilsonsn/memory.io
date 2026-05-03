<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Support\VersionedCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private const LIST_CACHE_TTL_SECONDS = 60;

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $notifications = VersionedCache::remember(
            namespace: 'notifications.list',
            params: [
                'per_page' => $perPage,
                'page' => $request->integer('page', 1),
            ],
            ttlSeconds: self::LIST_CACHE_TTL_SECONDS,
            callback: static fn () => Notification::query()
                ->latest()
                ->paginate($perPage),
            scope: auth()->id(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully.',
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }
}
