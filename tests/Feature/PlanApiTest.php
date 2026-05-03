<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_plans(): void
    {
        $this->getJson('/api/plans')
            ->assertUnauthorized();
    }

    public function test_regular_user_cannot_access_plans(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(UserRole::USER->value);

        $this->actingAs($user, 'api')
            ->getJson('/api/plans')
            ->assertForbidden();
    }

    public function test_authenticated_user_can_list_plans(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(UserRole::ADMIN->value);
        $plan = Plan::factory()->create([
            'name' => 'Premium',
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/plans')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $plan->id,
                'name' => 'Premium',
            ]);
    }

    public function test_authenticated_user_can_create_plan(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(UserRole::ADMIN->value);

        $this->actingAs($user, 'api')
            ->postJson('/api/plans', [
                'name' => 'Starter',
                'description' => 'Starter plan',
                'amount' => '19.90',
                'max_memories' => 100,
                'max_categories' => 10,
                'can_export' => false,
                'can_use_ai' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'name' => 'Starter',
                'description' => 'Starter plan',
                'max_memories' => 100,
                'max_categories' => 10,
                'can_export' => false,
                'can_use_ai' => false,
            ]);

        $this->assertDatabaseHas('plans', [
            'name' => 'Starter',
            'amount' => '19.90',
            'max_memories' => 100,
            'max_categories' => 10,
            'can_export' => false,
            'can_use_ai' => false,
        ]);
    }

    public function test_authenticated_user_can_show_update_and_delete_plan(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(UserRole::ADMIN->value);
        $plan = Plan::factory()->create([
            'name' => 'Basic',
            'amount' => '9.90',
        ]);

        $this->actingAs($user, 'api')
            ->getJson("/api/plans/{$plan->id}")
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Basic',
            ]);

        $this->actingAs($user, 'api')
            ->patchJson("/api/plans/{$plan->id}", [
                'name' => 'Business',
                'amount' => '49.90',
                'max_memories' => 500,
                'max_categories' => 50,
                'can_export' => true,
                'can_use_ai' => true,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Business',
                'max_memories' => 500,
                'max_categories' => 50,
                'can_export' => true,
                'can_use_ai' => true,
            ]);

        $this->actingAs($user, 'api')
            ->deleteJson("/api/plans/{$plan->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('plans', [
            'id' => $plan->id,
        ]);
    }
}
