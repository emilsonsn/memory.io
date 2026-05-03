<?php

namespace App\Http\Controllers;

use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Services\Plan\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function __construct(private readonly PlanService $planService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));
        $plans = $this->planService->getAll($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Plans retrieved successfully.',
            'data' => PlanResource::collection($plans),
            'meta' => [
                'current_page' => $plans->currentPage(),
                'last_page' => $plans->lastPage(),
                'per_page' => $plans->perPage(),
                'total' => $plans->total(),
            ],
        ]);
    }

    public function show(Plan $plan): JsonResponse
    {
        $plan = $this->planService
            ->setPlan($plan)
            ->object();

        return response()->json([
            'success' => true,
            'message' => 'Plan retrieved successfully.',
            'data' => PlanResource::make($plan),
        ]);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = $this->planService
            ->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully.',
            'data' => PlanResource::make($plan),
        ], 201);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        $updatedPlan = $this->planService
            ->setPlan($plan)
            ->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Plan updated successfully.',
            'data' => PlanResource::make($updatedPlan),
        ]);
    }

    public function destroy(Plan $plan): JsonResponse
    {
        $this->planService
            ->setPlan($plan)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan deleted successfully.',
            'data' => null,
        ]);
    }
}
