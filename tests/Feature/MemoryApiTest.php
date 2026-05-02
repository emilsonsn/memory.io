<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Memory;
use App\Models\User;
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
        $user = User::factory()->create();
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
        $user = User::factory()->create();
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
        $user = User::factory()->create();
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
}
