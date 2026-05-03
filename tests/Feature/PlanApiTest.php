<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
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

    public function test_authenticated_user_can_list_plans(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'name' => 'Premium',
        ]);

        $this->actingAs($user)
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
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/plans', [
                'name' => 'Starter',
                'description' => 'Starter plan',
                'amount' => '19.90',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'name' => 'Starter',
                'description' => 'Starter plan',
            ]);

        $this->assertDatabaseHas('plans', [
            'name' => 'Starter',
            'amount' => '19.90',
        ]);
    }

    public function test_authenticated_user_can_show_update_and_delete_plan(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'name' => 'Basic',
            'amount' => '9.90',
        ]);

        $this->actingAs($user)
            ->getJson("/api/plans/{$plan->id}")
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Basic',
            ]);

        $this->actingAs($user)
            ->patchJson("/api/plans/{$plan->id}", [
                'name' => 'Business',
                'amount' => '49.90',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Business',
            ]);

        $this->actingAs($user)
            ->deleteJson("/api/plans/{$plan->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('plans', [
            'id' => $plan->id,
        ]);
    }
}
