<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\Plan;
use App\Models\User;
use App\Services\Plan\PlanLimitService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reads_export_and_ai_capabilities_from_plan(): void
    {
        $plan = Plan::factory()->create([
            'can_export' => true,
            'can_use_ai' => false,
        ]);
        $user = User::factory()->for($plan)->create();

        $service = app(PlanLimitService::class);

        $this->assertTrue($service->canExport($user));
        $this->assertFalse($service->canUseAi($user));
    }

    public function test_admin_can_export_and_use_ai_without_plan_flags(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $plan = Plan::factory()->create([
            'can_export' => false,
            'can_use_ai' => false,
        ]);
        $user = User::factory()->for($plan)->create();
        $user->assignRole(UserRole::ADMIN->value);

        $service = app(PlanLimitService::class);

        $this->assertTrue($service->canExport($user));
        $this->assertTrue($service->canUseAi($user));
    }
}
