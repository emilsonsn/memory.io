<?php

namespace App\Services\Plan;

use App\Models\Plan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class PlanService
{
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
        return Plan::query()
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Plan
    {
        $this->plan = Plan::create($data);

        return $this->object();
    }

    public function update(array $data): Plan
    {
        $this->verifyPlanIsSet();

        $this->plan->update($data);

        return $this->object();
    }

    public function delete(): self
    {
        $this->verifyPlanIsSet();
        $this->plan->delete();

        return $this;
    }
}
