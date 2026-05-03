<?php

namespace App\Services\Plan;

use App\Models\Plan;
use App\Support\VersionedCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class PlanService
{
    private const LIST_CACHE_TTL_SECONDS = 300;

    private Plan $plan;

    public function setPlan(Plan $plan): self
    {
        $this->plan = $plan;

        return $this;
    }

    public function object(): Plan
    {
        return $this->plan->fresh();
    }

    public function verifyPlanIsSet(): void
    {
        if (! isset($this->plan)) {
            throw new RuntimeException('Plan is not set.');
        }
    }

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        $page = request()->integer('page', 1);

        return VersionedCache::remember(
            namespace: 'plans.list',
            params: [
                'per_page' => $perPage,
                'page' => $page,
            ],
            ttlSeconds: self::LIST_CACHE_TTL_SECONDS,
            callback: static fn () => Plan::query()
                ->latest()
                ->paginate($perPage),
        );
    }

    public function create(array $data): Plan
    {
        $this->plan = Plan::create($data);

        VersionedCache::bump('plans.list');

        return $this->object();
    }

    public function update(array $data): Plan
    {
        $this->verifyPlanIsSet();

        $this->plan->update($data);

        VersionedCache::bump('plans.list');

        return $this->object();
    }

    public function delete(): self
    {
        $this->verifyPlanIsSet();
        $this->plan->delete();

        VersionedCache::bump('plans.list');

        return $this;
    }
}
