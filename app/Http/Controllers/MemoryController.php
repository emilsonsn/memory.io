<?php

namespace App\Http\Controllers;

use App\Http\Requests\Memory\IndexMemoryRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Requests\Memory\StoreMemoryRequest;
use App\Http\Requests\Memory\UpdateMemoryRequest;
use App\Http\Resources\MemoryResource;
use App\Models\Memory;
use App\Services\Memory\MemoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemoryController extends Controller
{
    public function __construct(private readonly MemoryService $memoryService) {}

    public function index(IndexMemoryRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = $filters['per_page'] ?? 15;
        unset($filters['per_page']);

        $memories = $this->memoryService->getAll($perPage, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Memories retrieved successfully.',
            'data' => MemoryResource::collection($memories),
            'meta' => [
                'current_page' => $memories->currentPage(),
                'last_page' => $memories->lastPage(),
                'per_page' => $memories->perPage(),
                'total' => $memories->total(),
            ],
        ]);
    }

    public function show(Memory $memory): JsonResponse
    {
        $memory = $this->memoryService
            ->setMemory($memory)
            ->object();

        return response()->json([
            'success' => true,
            'message' => 'Memory retrieved successfully.',
            'data' => MemoryResource::make($memory),
        ]);
    }

    public function logs(Request $request, Memory $memory): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $logs = $this->memoryService->getLogs($memory, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Memory logs retrieved successfully.',
            'data' => ActivityResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function export(Memory $memory): StreamedResponse
    {
        $export = $this->memoryService->exportAsText($memory);

        return response()->streamDownload(
            static function () use ($export): void {
                echo $export['content'];
            },
            $export['filename'],
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ],
        );
    }

    public function store(StoreMemoryRequest $request): JsonResponse
    {
        $memory = $this->memoryService
            ->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Memory created successfully.',
            'data' => MemoryResource::make($memory),
        ], 201);
    }

    public function update(UpdateMemoryRequest $request, Memory $memory): JsonResponse
    {
        $updatedMemory = $this->memoryService
            ->setMemory($memory)
            ->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Memory updated successfully.',
            'data' => MemoryResource::make($updatedMemory),
        ]);
    }

    public function destroy(Memory $memory): JsonResponse
    {
        $this->memoryService
            ->setMemory($memory)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Memory deleted successfully.',
            'data' => null,
        ]);
    }
}
