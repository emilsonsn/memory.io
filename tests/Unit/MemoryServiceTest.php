<?php

namespace Tests\Unit;

use App\Exceptions\PlanLimitExceededException;
use App\Models\Category;
use App\Models\Memory;
use App\Models\Plan;
use App\Models\User;
use App\Services\Memory\MemoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_memory_for_authenticated_user(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 100,
        ]);
        $user = User::factory()->for($plan)->create();

        $this->actingAs($user);

        $memory = app(MemoryService::class)->create([
            'title' => 'Call mom',
            'content' => 'Call mom on Sunday.',
            'due_date' => now()->addDay(),
        ]);

        $this->assertSame('Call mom', $memory->title);
        $this->assertDatabaseHas('memories', [
            'id' => $memory->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_it_syncs_only_owned_categories(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 100,
        ]);
        $user = User::factory()->for($plan)->create();
        $otherUser = User::factory()->create();
        $ownedCategory = Category::factory()->for($user)->create();
        $otherUsersCategory = Category::factory()->for($otherUser)->create();

        $this->actingAs($user);

        $memory = app(MemoryService::class)->create([
            'title' => 'Plan trip',
            'content' => 'Pick destination and dates.',
            'category_ids' => [
                $ownedCategory->id,
                $otherUsersCategory->id,
            ],
        ]);

        $this->assertDatabaseHas('category_memory', [
            'memory_id' => $memory->id,
            'category_id' => $ownedCategory->id,
        ]);

        $this->assertDatabaseMissing('category_memory', [
            'memory_id' => $memory->id,
            'category_id' => $otherUsersCategory->id,
        ]);
    }

    public function test_it_duplicates_memory_with_categories_for_authenticated_user(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 100,
        ]);
        $user = User::factory()->for($plan)->create();
        $category = Category::factory()->for($user)->create();
        $memory = Memory::factory()->for($user)->create([
            'title' => 'Original memory',
            'content' => 'Original content.',
            'color' => 'blue',
            'due_date' => '2026-07-15 10:30:00',
        ]);
        $memory->categories()->sync([$category->id]);

        $this->actingAs($user);

        $duplicatedMemory = app(MemoryService::class)->duplicate($memory);

        $this->assertNotSame($memory->id, $duplicatedMemory->id);
        $this->assertSame('Original memory', $duplicatedMemory->title);
        $this->assertSame('Original content.', $duplicatedMemory->content);
        $this->assertSame('blue', $duplicatedMemory->color->value);
        $this->assertSame($user->id, $duplicatedMemory->user_id);

        $this->assertDatabaseHas('category_memory', [
            'memory_id' => $duplicatedMemory->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_it_lists_only_authenticated_users_memories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Memory::factory()->for($user)->create();
        Memory::factory()->for($otherUser)->count(2)->create();

        $this->actingAs($user);

        $memories = app(MemoryService::class)->getAll();

        $this->assertSame(1, $memories->total());
    }

    public function test_it_blocks_memory_creation_after_plan_limit(): void
    {
        $plan = Plan::factory()->create([
            'max_memories' => 1,
        ]);
        $user = User::factory()->for($plan)->create();

        Memory::factory()->for($user)->create();

        $this->actingAs($user);

        $this->expectException(PlanLimitExceededException::class);

        app(MemoryService::class)->create([
            'title' => 'Blocked memory',
            'content' => 'This should not be created.',
        ]);
    }
}
