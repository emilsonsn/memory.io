<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Memory;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
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

        $this->actingAs($user, 'api')
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

    public function test_user_can_filter_memories_by_text_dates_and_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $targetCategory = Category::factory()->for($user)->create();
        $otherCategory = Category::factory()->for($user)->create();
        $externalCategory = Category::factory()->for($otherUser)->create();

        $matchingMemory = Memory::factory()->for($user)->create([
            'title' => 'Project kickoff notes',
            'content' => 'Discuss timeline and deliverables.',
            'due_date' => '2026-05-20 10:00:00',
            'created_at' => '2026-05-10 09:00:00',
            'updated_at' => '2026-05-12 14:00:00',
        ]);
        $matchingMemory->categories()->sync([$targetCategory->id]);

        $wrongDueDateMemory = Memory::factory()->for($user)->create([
            'title' => 'Project kickoff draft',
            'content' => 'This one has due date out of range.',
            'due_date' => '2026-06-10 10:00:00',
            'created_at' => '2026-05-10 09:00:00',
            'updated_at' => '2026-05-12 14:00:00',
        ]);
        $wrongDueDateMemory->categories()->sync([$targetCategory->id]);

        $wrongCategoryMemory = Memory::factory()->for($user)->create([
            'title' => 'Project kickoff retrospective',
            'content' => 'Same text, but wrong category.',
            'due_date' => '2026-05-20 10:00:00',
            'created_at' => '2026-05-10 09:00:00',
            'updated_at' => '2026-05-12 14:00:00',
        ]);
        $wrongCategoryMemory->categories()->sync([$otherCategory->id]);

        $otherUserMemory = Memory::factory()->for($otherUser)->create([
            'title' => 'Project kickoff private',
            'content' => 'Should not be visible for authenticated user.',
            'due_date' => '2026-05-20 10:00:00',
            'created_at' => '2026-05-10 09:00:00',
            'updated_at' => '2026-05-12 14:00:00',
        ]);
        $otherUserMemory->categories()->sync([$externalCategory->id]);

        $query = http_build_query([
            'created_from' => '2026-05-01',
            'created_to' => '2026-05-31',
            'updated_from' => '2026-05-01',
            'updated_to' => '2026-05-31',
            'due_from' => '2026-05-01',
            'due_to' => '2026-05-31',
            'text' => 'kickoff',
            'category_ids' => [$targetCategory->id],
        ]);

        $this->actingAs($user, 'api')
            ->getJson("/api/memories?{$query}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $matchingMemory->id,
                'title' => 'Project kickoff notes',
            ])
            ->assertJsonMissing([
                'id' => $wrongDueDateMemory->id,
            ])
            ->assertJsonMissing([
                'id' => $wrongCategoryMemory->id,
            ])
            ->assertJsonMissing([
                'id' => $otherUserMemory->id,
            ]);
    }

    public function test_user_can_create_memory_with_categories(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 100,
        ]);
        $user = User::factory()->for($plan)->create();
        $category = Category::factory()->for($user)->create();

        $this->actingAs($user, 'api')
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

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'audit',
            'event' => 'memory.created',
            'description' => 'memory.created',
            'subject_type' => Memory::class,
            'subject_id' => $memory->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $activity = Activity::query()
            ->where('event', 'memory.created')
            ->where('subject_id', $memory->id)
            ->firstOrFail();

        $this->assertSame('Buy coffee', data_get($activity->properties->toArray(), 'new.title'));
        $this->assertSame($category->id, data_get($activity->properties->toArray(), 'new.category_ids.0'));
    }

    public function test_user_cannot_attach_another_users_category_to_memory(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 100,
        ]);
        $user = User::factory()->for($plan)->create();
        $otherUser = User::factory()->create();
        $otherUsersCategory = Category::factory()->for($otherUser)->create();

        $this->actingAs($user, 'api')
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

        $this->actingAs($user, 'api')
            ->patchJson("/api/memories/{$memory->id}", [
                'title' => 'New title',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'New title',
            ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'audit',
            'event' => 'memory.updated',
            'description' => 'memory.updated',
            'subject_type' => Memory::class,
            'subject_id' => $memory->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $updateActivity = Activity::query()
            ->where('event', 'memory.updated')
            ->where('subject_id', $memory->id)
            ->firstOrFail();

        $this->assertSame('Old title', data_get($updateActivity->properties->toArray(), 'old.title'));
        $this->assertSame('New title', data_get($updateActivity->properties->toArray(), 'new.title'));

        $this->actingAs($user, 'api')
            ->deleteJson("/api/memories/{$memory->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'audit',
            'event' => 'memory.deleted',
            'description' => 'memory.deleted',
            'subject_type' => Memory::class,
            'subject_id' => $memory->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $deleteActivity = Activity::query()
            ->where('event', 'memory.deleted')
            ->where('subject_id', $memory->id)
            ->firstOrFail();

        $this->assertSame('New title', data_get($deleteActivity->properties->toArray(), 'old.title'));
        $this->assertNull(data_get($deleteActivity->properties->toArray(), 'new'));

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

        $this->actingAs($user, 'api')
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

        $this->actingAs($user, 'api')
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
