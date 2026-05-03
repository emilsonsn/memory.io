<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Services\Plan\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_plan(): void
    {
        $plan = app(PlanService::class)->create([
            'name' => 'Gold',
            'description' => 'Gold plan',
            'amount' => '99.90',
        ]);

        $this->assertSame('Gold', $plan->name);
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'amount' => '99.90',
        ]);
    }

    public function test_it_updates_plan(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'Old',
        ]);

        $updatedPlan = app(PlanService::class)
            ->setPlan($plan)
            ->update([
                'name' => 'New',
            ]);

        $this->assertSame('New', $updatedPlan->name);
    }

    public function test_it_deletes_plan(): void
    {
        $plan = Plan::factory()->create();

        app(PlanService::class)
            ->setPlan($plan)
            ->delete();

        $this->assertDatabaseMissing('plans', [
            'id' => $plan->id,
        ]);
    }
}
