<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Models\Category;
use App\Models\Memory;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;
use ZipArchive;

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
            'color' => 'blue',
        ]);

        Category::factory()->for($otherUser)->create([
            'label' => 'Hidden',
            'color' => 'red',
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $visibleCategory->id,
                'label' => 'Personal',
                'color' => 'blue',
            ])
            ->assertJsonMissing([
                'label' => 'Hidden',
            ]);
    }

    public function test_user_can_filter_categories_by_color(): void
    {
        $user = User::factory()->create();

        $blueCategory = Category::factory()->for($user)->create([
            'label' => 'Blue category',
            'color' => 'blue',
        ]);

        $redCategory = Category::factory()->for($user)->create([
            'label' => 'Red category',
            'color' => 'red',
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/categories?color=blue')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $blueCategory->id,
                'color' => 'blue',
            ])
            ->assertJsonMissing([
                'id' => $redCategory->id,
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
                'color' => 'green',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'label' => 'Work',
                'description' => 'Work related memories',
                'color' => 'green',
            ]);

        $this->assertDatabaseHas('categories', [
            'label' => 'Work',
            'color' => 'green',
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
        $this->assertSame('green', data_get($activity->properties->toArray(), 'new.color'));
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

    public function test_user_can_request_category_export_and_receive_notification_with_download_link(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $root = Category::factory()->for($user)->create([
            'label' => 'Root Folder',
            'parent_id' => null,
        ]);

        $child = Category::factory()->for($user)->create([
            'label' => 'Child Folder',
            'parent_id' => $root->id,
        ]);

        $rootMemory = Memory::factory()->for($user)->create([
            'title' => 'Root Note',
            'content' => 'Root content',
        ]);
        $rootMemory->categories()->sync([$root->id]);

        $childMemory = Memory::factory()->for($user)->create([
            'title' => 'Child Note',
            'content' => 'Child content',
        ]);
        $childMemory->categories()->sync([$child->id]);

        $this->actingAs($user, 'api')
            ->postJson("/api/categories/{$root->id}/export")
            ->assertStatus(202)
            ->assertJsonPath('success', true);

        $notification = Notification::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('type', NotificationType::PROCESS->value)
            ->latest()
            ->firstOrFail();

        $this->assertStringContainsString('/storage/exports/categories/'.$user->id.'/', (string) $notification->url);

        $urlPath = parse_url((string) $notification->url, PHP_URL_PATH) ?? '';
        $relativePath = Str::after($urlPath, '/storage/');

        Storage::disk('public')->assertExists($relativePath);

        $zipPath = Storage::disk('public')->path($relativePath);
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($zipPath) === true);

        $rootFile = 'Root Folder/Root Note.txt';
        $childFile = 'Root Folder/Child Folder/Child Note.txt';

        $this->assertNotFalse($zip->locateName($rootFile));
        $this->assertNotFalse($zip->locateName($childFile));

        $this->assertSame("Root Note\nRoot content", $zip->getFromName($rootFile));
        $this->assertSame("Child Note\nChild content", $zip->getFromName($childFile));

        $zip->close();
    }
}
