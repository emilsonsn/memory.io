<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Memory;
use App\Models\User;
use App\Services\Favorite\FavoriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FavoriteServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_memory_favorite_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $memory = Memory::factory()->for($user)->create();

        $this->actingAs($user);

        $favorite = app(FavoriteService::class)->add([
            'memory_id' => $memory->id,
        ]);

        $this->assertSame($memory->id, $favorite->memory_id);
        $this->assertNull($favorite->category_id);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'memory_id' => $memory->id,
        ]);
    }

    public function test_it_adds_category_favorite_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $this->actingAs($user);

        $favorite = app(FavoriteService::class)->add([
            'category_id' => $category->id,
        ]);

        $this->assertSame($category->id, $favorite->category_id);
        $this->assertNull($favorite->memory_id);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_it_lists_only_authenticated_users_favorites(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $memory = Memory::factory()->for($user)->create();
        $otherMemory = Memory::factory()->for($otherUser)->create();

        Favorite::factory()->for($user)->create([
            'memory_id' => $memory->id,
            'category_id' => null,
        ]);
        Favorite::factory()->for($otherUser)->create([
            'memory_id' => $otherMemory->id,
            'category_id' => null,
        ]);

        $this->actingAs($user);

        $favorites = app(FavoriteService::class)->getAll();

        $this->assertSame(1, $favorites->total());
    }

    public function test_it_rejects_other_users_target(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherMemory = Memory::factory()->for($otherUser)->create();

        $this->actingAs($user);

        $this->expectException(InvalidArgumentException::class);

        app(FavoriteService::class)->add([
            'memory_id' => $otherMemory->id,
        ]);
    }

    public function test_it_removes_favorite_by_target(): void
    {
        $user = User::factory()->create();
        $memory = Memory::factory()->for($user)->create();

        Favorite::factory()->for($user)->create([
            'memory_id' => $memory->id,
            'category_id' => null,
        ]);

        $this->actingAs($user);

        $removed = app(FavoriteService::class)->remove([
            'memory_id' => $memory->id,
        ]);

        $this->assertTrue($removed);
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'memory_id' => $memory->id,
        ]);
    }

    public function test_it_rejects_favorite_without_target(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->expectException(InvalidArgumentException::class);

        app(FavoriteService::class)->add([]);
    }

    public function test_it_rejects_favorite_with_memory_and_category_together(): void
    {
        $user = User::factory()->create();
        $memory = Memory::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();

        $this->actingAs($user);

        $this->expectException(InvalidArgumentException::class);

        app(FavoriteService::class)->add([
            'memory_id' => $memory->id,
            'category_id' => $category->id,
        ]);
    }
}
