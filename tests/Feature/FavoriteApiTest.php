<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_favorites(): void
    {
        $this->getJson('/api/favorites')
            ->assertUnauthorized();
    }

    public function test_user_can_favorite_memory(): void
    {
        $user = User::factory()->create();
        $memory = Memory::factory()->for($user)->create([
            'title' => 'Favorite memory',
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/favorites', [
                'memory_id' => $memory->id,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'memory')
            ->assertJsonPath('data.memory_id', $memory->id)
            ->assertJsonPath('data.memory.title', 'Favorite memory');

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'memory_id' => $memory->id,
            'category_id' => null,
        ]);
    }

    public function test_user_can_favorite_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create([
            'label' => 'Favorite category',
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/favorites', [
                'category_id' => $category->id,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'category')
            ->assertJsonPath('data.category_id', $category->id)
            ->assertJsonPath('data.category.label', 'Favorite category');

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'memory_id' => null,
            'category_id' => $category->id,
        ]);
    }

    public function test_favoriting_same_memory_twice_is_idempotent(): void
    {
        $user = User::factory()->create();
        $memory = Memory::factory()->for($user)->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/favorites', [
                'memory_id' => $memory->id,
            ])
            ->assertCreated();

        $this->actingAs($user, 'api')
            ->postJson('/api/favorites', [
                'memory_id' => $memory->id,
            ])
            ->assertCreated();

        $this->assertSame(1, Favorite::query()->where('memory_id', $memory->id)->count());
    }

    public function test_user_can_list_only_their_favorites(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $memory = Memory::factory()->for($user)->create([
            'title' => 'Visible favorite',
        ]);
        $category = Category::factory()->for($user)->create([
            'label' => 'Visible category',
        ]);
        $otherMemory = Memory::factory()->for($otherUser)->create([
            'title' => 'Hidden favorite',
        ]);

        Favorite::factory()->for($user)->create([
            'memory_id' => $memory->id,
            'category_id' => null,
        ]);
        Favorite::factory()->for($user)->create([
            'memory_id' => null,
            'category_id' => $category->id,
        ]);
        Favorite::factory()->for($otherUser)->create([
            'memory_id' => $otherMemory->id,
            'category_id' => null,
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/favorites')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment([
                'title' => 'Visible favorite',
            ])
            ->assertJsonFragment([
                'label' => 'Visible category',
            ])
            ->assertJsonMissing([
                'title' => 'Hidden favorite',
            ]);
    }

    public function test_user_can_remove_memory_from_favorites(): void
    {
        $user = User::factory()->create();
        $memory = Memory::factory()->for($user)->create();

        Favorite::factory()->for($user)->create([
            'memory_id' => $memory->id,
            'category_id' => null,
        ]);

        $this->actingAs($user, 'api')
            ->deleteJson('/api/favorites', [
                'memory_id' => $memory->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'memory_id' => $memory->id,
        ]);
    }

    public function test_user_can_remove_category_from_favorites(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        Favorite::factory()->for($user)->create([
            'memory_id' => null,
            'category_id' => $category->id,
        ]);

        $this->actingAs($user, 'api')
            ->deleteJson('/api/favorites', [
                'category_id' => $category->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_user_cannot_favorite_other_users_memory_or_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherMemory = Memory::factory()->for($otherUser)->create();
        $otherCategory = Category::factory()->for($otherUser)->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/favorites', [
                'memory_id' => $otherMemory->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('memory_id');

        $this->actingAs($user, 'api')
            ->postJson('/api/favorites', [
                'category_id' => $otherCategory->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }

    public function test_favorite_payload_must_have_exactly_one_target(): void
    {
        $user = User::factory()->create();
        $memory = Memory::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/favorites', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['memory_id', 'category_id']);

        $this->actingAs($user, 'api')
            ->postJson('/api/favorites', [
                'memory_id' => $memory->id,
                'category_id' => $category->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['memory_id', 'category_id']);
    }
}
