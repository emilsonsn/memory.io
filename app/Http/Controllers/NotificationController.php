<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $notifications = $this->notificationService->getAll($perPage);

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

    public function read(Notification $notification): JsonResponse
    {
        $notification = $this->notificationService->read($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as seen successfully.',
            'data' => NotificationResource::make($notification),
        ]);
    }
}
