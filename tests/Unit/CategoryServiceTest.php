<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\User;
use App\Services\Category\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_category_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $category = app(CategoryService::class)->create([
            'label' => 'Books',
            'description' => 'Book notes',
        ]);

        $this->assertSame('Books', $category->label);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_it_lists_only_authenticated_users_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Category::factory()->for($user)->create();
        Category::factory()->for($otherUser)->count(2)->create();

        $this->actingAs($user);

        $categories = app(CategoryService::class)->getAll();

        $this->assertSame(1, $categories->total());
    }
}
