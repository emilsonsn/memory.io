<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Memory;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_memories(): void
    {
        $this->getJson('/api/memories')
            ->assertUnauthorized();
    }

    public function test_user_can_list_only_their_memories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $visibleMemory = Memory::factory()->for($user)->create([
            'title' => 'Visible memory',
        ]);

        Memory::factory()->for($otherUser)->create([
            'title' => 'Hidden memory',
        ]);

        $this->actingAs($user)
            ->getJson('/api/memories')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $visibleMemory->id,
                'title' => 'Visible memory',
            ])
            ->assertJsonMissing([
                'title' => 'Hidden memory',
            ]);
    }

    public function test_user_can_create_memory_with_categories(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 100,
        ]);
        $user = User::factory()->for($plan)->create();
        $category = Category::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson('/api/memories', [
                'title' => 'Buy coffee',
                'content' => 'Remember to buy coffee tomorrow.',
                'due_date' => now()->addDay()->toISOString(),
                'category_ids' => [$category->id],
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'title' => 'Buy coffee',
            ])
            ->assertJsonFragment([
                'id' => $category->id,
            ]);

        $memory = Memory::query()->where('title', 'Buy coffee')->firstOrFail();

        $this->assertDatabaseHas('memories', [
            'id' => $memory->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('category_memory', [
            'memory_id' => $memory->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_user_cannot_attach_another_users_category_to_memory(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 100,
        ]);
        $user = User::factory()->for($plan)->create();
        $otherUser = User::factory()->create();
        $otherUsersCategory = Category::factory()->for($otherUser)->create();

        $this->actingAs($user)
            ->postJson('/api/memories', [
                'title' => 'Buy tea',
                'content' => 'Remember to buy tea tomorrow.',
                'due_date' => now()->addDay()->toISOString(),
                'category_ids' => [$otherUsersCategory->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_ids.0');
    }

    public function test_user_can_update_and_delete_memory(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 100,
        ]);
        $user = User::factory()->for($plan)->create();
        $memory = Memory::factory()->for($user)->create([
            'title' => 'Old title',
        ]);

        $this->actingAs($user)
            ->patchJson("/api/memories/{$memory->id}", [
                'title' => 'New title',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'New title',
            ]);

        $this->actingAs($user)
            ->deleteJson("/api/memories/{$memory->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('memories', [
            'id' => $memory->id,
        ]);
    }

    public function test_user_cannot_create_memory_after_reaching_plan_limit(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 1,
        ]);
        $user = User::factory()->for($plan)->create();

        Memory::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson('/api/memories', [
                'title' => 'Blocked memory',
                'content' => 'This should not be created.',
                'due_date' => now()->addDay()->toISOString(),
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'PLAN_LIMIT_EXCEEDED');
    }

    public function test_admin_can_create_memory_without_plan_limit(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'plan_id' => null,
        ]);
        $user->assignRole('admin');

        $this->actingAs($user)
            ->postJson('/api/memories', [
                'title' => 'Admin memory',
                'content' => 'Admins are not limited by plans.',
                'due_date' => now()->addDay()->toISOString(),
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'title' => 'Admin memory',
            ]);
    }
}
