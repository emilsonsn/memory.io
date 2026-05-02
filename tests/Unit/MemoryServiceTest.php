<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Memory;
use App\Models\User;
use App\Services\Memory\MemoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_memory_for_authenticated_user(): void
    {
        $user = User::factory()->create();

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
        $user = User::factory()->create();
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
}
