<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_categories(): void
    {
        $this->getJson('/api/categories')
            ->assertUnauthorized();
    }

    public function test_user_can_list_only_their_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $visibleCategory = Category::factory()->for($user)->create([
            'label' => 'Personal',
        ]);

        Category::factory()->for($otherUser)->create([
            'label' => 'Hidden',
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $visibleCategory->id,
                'label' => 'Personal',
            ])
            ->assertJsonMissing([
                'label' => 'Hidden',
            ]);
    }

    public function test_user_can_create_category(): void
    {
        $plan = Plan::factory()->create([
            'max_categories' => 10,
        ]);
        $user = User::factory()->for($plan)->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/categories', [
                'label' => 'Work',
                'description' => 'Work related memories',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'label' => 'Work',
                'description' => 'Work related memories',
            ]);

        $this->assertDatabaseHas('categories', [
            'label' => 'Work',
            'user_id' => $user->id,
        ]);

        $category = Category::query()->where('label', 'Work')->firstOrFail();

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'audit',
            'event' => 'category.created',
            'description' => 'category.created',
            'subject_type' => Category::class,
            'subject_id' => $category->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $activity = Activity::query()
            ->where('event', 'category.created')
            ->where('subject_id', $category->id)
            ->firstOrFail();

        $this->assertSame('Work', data_get($activity->properties->toArray(), 'new.label'));
    }

    public function test_user_can_update_category_and_generate_audit_log(): void
    {
        $plan = Plan::factory()->create([
            'max_categories' => 10,
        ]);
        $user = User::factory()->for($plan)->create();
        $category = Category::factory()->for($user)->create([
            'label' => 'Old label',
            'description' => 'Old description',
        ]);

        $this->actingAs($user, 'api')
            ->patchJson("/api/categories/{$category->id}", [
                'label' => 'Updated label',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'label' => 'Updated label',
            ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'audit',
            'event' => 'category.updated',
            'description' => 'category.updated',
            'subject_type' => Category::class,
            'subject_id' => $category->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $activity = Activity::query()
            ->where('event', 'category.updated')
            ->where('subject_id', $category->id)
            ->firstOrFail();

        $this->assertSame('Old label', data_get($activity->properties->toArray(), 'old.label'));
        $this->assertSame('Updated label', data_get($activity->properties->toArray(), 'new.label'));
    }

    public function test_user_can_delete_category_and_generate_audit_log(): void
    {
        $plan = Plan::factory()->create([
            'max_categories' => 10,
        ]);
        $user = User::factory()->for($plan)->create();
        $category = Category::factory()->for($user)->create([
            'label' => 'Delete me',
            'description' => 'Category to be deleted',
        ]);

        $this->actingAs($user, 'api')
            ->deleteJson("/api/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'audit',
            'event' => 'category.deleted',
            'description' => 'category.deleted',
            'subject_type' => Category::class,
            'subject_id' => $category->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $activity = Activity::query()
            ->where('event', 'category.deleted')
            ->where('subject_id', $category->id)
            ->firstOrFail();

        $this->assertSame('Delete me', data_get($activity->properties->toArray(), 'old.label'));
        $this->assertNull(data_get($activity->properties->toArray(), 'new'));
    }

    public function test_user_cannot_use_another_users_category_as_parent(): void
    {
        $plan = Plan::factory()->create([
            'max_categories' => 10,
        ]);
        $user = User::factory()->for($plan)->create();
        $otherUser = User::factory()->create();
        $otherUsersCategory = Category::factory()->for($otherUser)->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/categories', [
                'label' => 'Private',
                'description' => 'Private category',
                'parent_id' => $otherUsersCategory->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_user_cannot_create_category_after_reaching_plan_limit(): void
    {
        $plan = Plan::factory()->create([
            'max_categories' => 1,
        ]);
        $user = User::factory()->for($plan)->create();

        Category::factory()->for($user)->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/categories', [
                'label' => 'Blocked',
                'description' => 'This should not be created.',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'PLAN_LIMIT_EXCEEDED');
    }
}
