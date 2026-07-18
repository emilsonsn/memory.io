<?php

namespace Tests\Feature;

use App\Enums\UserRole;
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
            'color' => 'blue',
            'due_date' => '2026-05-20 10:00:00',
            'created_at' => '2026-05-10 09:00:00',
            'updated_at' => '2026-05-12 14:00:00',
            'category_id' => $targetCategory->id,
        ]);

        $wrongDueDateMemory = Memory::factory()->for($user)->create([
            'title' => 'Project kickoff draft',
            'content' => 'This one has due date out of range.',
            'color' => 'blue',
            'due_date' => '2026-06-10 10:00:00',
            'created_at' => '2026-05-10 09:00:00',
            'updated_at' => '2026-05-12 14:00:00',
            'category_id' => $targetCategory->id,
        ]);

        $wrongCategoryMemory = Memory::factory()->for($user)->create([
            'title' => 'Project kickoff retrospective',
            'content' => 'Same text, but wrong category.',
            'color' => 'blue',
            'due_date' => '2026-05-20 10:00:00',
            'created_at' => '2026-05-10 09:00:00',
            'updated_at' => '2026-05-12 14:00:00',
            'category_id' => $otherCategory->id,
        ]);

        $otherUserMemory = Memory::factory()->for($otherUser)->create([
            'title' => 'Project kickoff private',
            'content' => 'Should not be visible for authenticated user.',
            'color' => 'blue',
            'due_date' => '2026-05-20 10:00:00',
            'created_at' => '2026-05-10 09:00:00',
            'updated_at' => '2026-05-12 14:00:00',
            'category_id' => $externalCategory->id,
        ]);

        $query = http_build_query([
            'created_from' => '2026-05-01',
            'created_to' => '2026-05-31',
            'updated_from' => '2026-05-01',
            'updated_to' => '2026-05-31',
            'due_from' => '2026-05-01',
            'due_to' => '2026-05-31',
            'text' => 'kickoff',
            'color' => 'blue',
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

    public function test_user_can_filter_memories_by_text_ignoring_case(): void
    {
        $user = User::factory()->create();

        $matchingMemory = Memory::factory()->for($user)->create([
            'title' => 'Important Mixed CASE Note',
            'content' => 'Plain content.',
        ]);

        Memory::factory()->for($user)->create([
            'title' => 'Another note',
            'content' => 'No matching text.',
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/memories?text=mixed case')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $matchingMemory->id,
                'title' => 'Important Mixed CASE Note',
            ])
            ->assertJsonMissing([
                'title' => 'Another note',
            ]);
    }

    public function test_user_can_filter_memories_without_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $uncategorizedMemory = Memory::factory()->for($user)->create([
            'title' => 'Loose note',
        ]);

        $categorizedMemory = Memory::factory()->for($user)->create([
            'title' => 'Categorized note',
        ]);
        $categorizedMemory->update(['category_id' => $category->id]);

        Memory::factory()->for($otherUser)->create([
            'title' => 'Other loose note',
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/memories?without_categories=true')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $uncategorizedMemory->id,
                'title' => 'Loose note',
            ])
            ->assertJsonMissing([
                'id' => $categorizedMemory->id,
            ])
            ->assertJsonMissing([
                'title' => 'Other loose note',
            ]);
    }

    public function test_user_cannot_combine_without_categories_with_category_ids(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $query = http_build_query([
            'without_categories' => true,
            'category_ids' => [$category->id],
        ]);

        $this->actingAs($user, 'api')
            ->getJson("/api/memories?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('without_categories');
    }

    public function test_user_can_list_only_their_trashed_memories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $trashedMemory = Memory::factory()->for($user)->create([
            'title' => 'Deleted memory',
        ]);
        $trashedMemory->delete();

        Memory::factory()->for($user)->create([
            'title' => 'Active memory',
        ]);

        $otherUsersTrashedMemory = Memory::factory()->for($otherUser)->create([
            'title' => 'Other deleted memory',
        ]);
        $otherUsersTrashedMemory->delete();

        $this->actingAs($user, 'api')
            ->getJson('/api/memories/trashed')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $trashedMemory->id,
                'title' => 'Deleted memory',
            ])
            ->assertJsonMissing([
                'title' => 'Active memory',
            ])
            ->assertJsonMissing([
                'title' => 'Other deleted memory',
            ]);
    }

    public function test_user_can_filter_trashed_memories_without_categories(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $uncategorizedTrashedMemory = Memory::factory()->for($user)->create([
            'title' => 'Deleted loose note',
        ]);
        $uncategorizedTrashedMemory->delete();

        $categorizedTrashedMemory = Memory::factory()->for($user)->create([
            'title' => 'Deleted categorized note',
        ]);
        $categorizedTrashedMemory->update(['category_id' => $category->id]);
        $categorizedTrashedMemory->delete();

        $this->actingAs($user, 'api')
            ->getJson('/api/memories/trashed?without_categories=true')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $uncategorizedTrashedMemory->id,
                'title' => 'Deleted loose note',
            ])
            ->assertJsonMissing([
                'id' => $categorizedTrashedMemory->id,
            ]);
    }

    public function test_user_can_sort_memories(): void
    {
        $user = User::factory()->create();

        Memory::factory()->for($user)->create([
            'title' => 'Alpha memory',
        ]);

        Memory::factory()->for($user)->create([
            'title' => 'Zulu memory',
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/memories?sort_by=title&sort_direction=desc')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Zulu memory')
            ->assertJsonPath('data.1.title', 'Alpha memory');
    }

    public function test_user_cannot_sort_memories_by_unsupported_column(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/memories?sort_by=user_id')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort_by');
    }

    public function test_user_can_list_logs_for_a_specific_memory(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 100,
        ]);
        $user = User::factory()->for($plan)->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/memories', [
                'title' => 'Target memory',
                'content' => 'First content',
                'due_date' => now()->addDay()->toISOString(),
            ])
            ->assertCreated();

        $targetMemory = Memory::query()->where('title', 'Target memory')->firstOrFail();

        $this->actingAs($user, 'api')
            ->patchJson("/api/memories/{$targetMemory->id}", [
                'title' => 'Target memory updated',
            ])
            ->assertOk();

        $this->actingAs($user, 'api')
            ->postJson('/api/memories', [
                'title' => 'Other memory',
                'content' => 'Other content',
                'due_date' => now()->addDay()->toISOString(),
            ])
            ->assertCreated();

        $otherMemory = Memory::query()->where('title', 'Other memory')->firstOrFail();

        $this->actingAs($user, 'api')
            ->getJson("/api/memories/{$targetMemory->id}/logs?per_page=10")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment([
                'event' => 'memory.created',
                'subject_id' => $targetMemory->id,
                'causer_id' => $user->id,
            ])
            ->assertJsonFragment([
                'event' => 'memory.updated',
                'subject_id' => $targetMemory->id,
                'causer_id' => $user->id,
            ])
            ->assertJsonMissing([
                'subject_id' => $otherMemory->id,
            ]);
    }

    public function test_user_can_export_memory_as_txt_file(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 100,
        ]);
        $user = User::factory()->for($plan)->create();

        $memory = Memory::factory()->for($user)->create([
            'title' => 'Shopping list',
            'content' => "Coffee\nMilk\nBread",
        ]);

        $response = $this->actingAs($user, 'api')
            ->get("/api/memories/{$memory->id}/export");

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename="Shopping list.txt"');

        $response
            ->assertStreamed()
            ->assertStreamedContent("Shopping list\nCoffee\nMilk\nBread");
    }

    public function test_user_can_create_memory_with_category(): void
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
                'color' => 'purple',
                'due_date' => now()->addDay()->toISOString(),
                'category_id' => $category->id,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'title' => 'Buy coffee',
                'color' => 'purple',
            ])
            ->assertJsonFragment([
                'id' => $category->id,
            ]);

        $memory = Memory::query()->where('title', 'Buy coffee')->firstOrFail();

        $this->assertDatabaseHas('memories', [
            'id' => $memory->id,
            'color' => 'purple',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('memories', [
            'id' => $memory->id,
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
        $this->assertSame('purple', data_get($activity->properties->toArray(), 'new.color'));
        $this->assertSame($category->id, data_get($activity->properties->toArray(), 'new.category_id'));
    }

    public function test_user_can_duplicate_memory_with_category(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 100,
        ]);
        $user = User::factory()->for($plan)->create();
        $category = Category::factory()->for($user)->create();

        $memory = Memory::factory()->for($user)->create([
            'title' => 'Original memory',
            'content' => 'Original content.',
            'color' => 'green',
            'due_date' => '2026-07-15 10:30:00',
        ]);
        $memory->update(['category_id' => $category->id]);

        $this->actingAs($user, 'api')
            ->postJson("/api/memories/{$memory->id}/duplicate")
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Memory duplicated successfully.')
            ->assertJsonPath('data.title', 'Original memory')
            ->assertJsonPath('data.content', 'Original content.')
            ->assertJsonPath('data.color', 'green')
            ->assertJsonFragment([
                'id' => $category->id,
            ]);

        $duplicatedMemory = Memory::query()
            ->where('title', 'Original memory')
            ->whereKeyNot($memory->id)
            ->firstOrFail();

        $this->assertNotSame($memory->id, $duplicatedMemory->id);

        $this->assertDatabaseHas('memories', [
            'id' => $duplicatedMemory->id,
            'user_id' => $user->id,
            'title' => 'Original memory',
            'content' => 'Original content.',
            'color' => 'green',
        ]);

        $this->assertDatabaseHas('memories', [
            'id' => $duplicatedMemory->id,
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'audit',
            'event' => 'memory.duplicated',
            'description' => 'memory.duplicated',
            'subject_type' => Memory::class,
            'subject_id' => $duplicatedMemory->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $activity = Activity::query()
            ->where('event', 'memory.duplicated')
            ->where('subject_id', $duplicatedMemory->id)
            ->firstOrFail();

        $this->assertSame($memory->id, data_get($activity->properties->toArray(), 'source_memory_id'));
        $this->assertSame('Original memory', data_get($activity->properties->toArray(), 'new.title'));
        $this->assertSame($category->id, data_get($activity->properties->toArray(), 'new.category_id'));
    }

    public function test_user_cannot_duplicate_another_users_memory(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 100,
        ]);
        $user = User::factory()->for($plan)->create();
        $otherUser = User::factory()->for($plan)->create();
        $memory = Memory::factory()->for($otherUser)->create();

        $this->actingAs($user, 'api')
            ->postJson("/api/memories/{$memory->id}/duplicate")
            ->assertNotFound();
    }

    public function test_user_cannot_duplicate_memory_after_reaching_plan_limit(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 1,
        ]);
        $user = User::factory()->for($plan)->create();

        $memory = Memory::factory()->for($user)->create();

        $this->actingAs($user, 'api')
            ->postJson("/api/memories/{$memory->id}/duplicate")
            ->assertForbidden()
            ->assertJsonPath('code', 'PLAN_LIMIT_EXCEEDED');
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
                'category_id' => $otherUsersCategory->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
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
        $user->assignRole(UserRole::ADMIN->value);

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
