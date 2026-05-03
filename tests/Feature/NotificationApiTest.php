<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_notifications(): void
    {
        $this->getJson('/api/notifications')
            ->assertUnauthorized();
    }

    public function test_user_can_list_only_their_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user, 'api');

        $visibleNotification = Notification::query()->create([
            'title' => 'Visible notification',
            'url' => '/memories',
            'type' => NotificationType::DEFAULT->value,
        ]);

        $this->actingAs($otherUser, 'api');

        Notification::query()->create([
            'title' => 'Hidden notification',
            'url' => '/memories',
            'type' => NotificationType::PROCESS->value,
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $visibleNotification->id,
                'title' => 'Visible notification',
                'seen' => false,
            ])
            ->assertJsonMissing([
                'title' => 'Hidden notification',
            ]);
    }

    public function test_user_cannot_create_notification_via_api(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/notifications', [
                'title' => 'New notification',
                'url' => '/memories',
                'type' => NotificationType::DEFAULT->value,
            ])
            ->assertMethodNotAllowed();
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $notification = Notification::query()->create([
            'title' => 'Unread notification',
            'url' => '/memories',
            'type' => NotificationType::DEFAULT->value,
        ]);

        $this->actingAs($user, 'api')
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $notification->id,
                'seen' => true,
            ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'seen' => true,
        ]);
    }

    public function test_user_can_mark_multiple_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $first = Notification::query()->create([
            'title' => 'First unread',
            'url' => '/memories',
            'type' => NotificationType::DEFAULT->value,
        ]);

        $second = Notification::query()->create([
            'title' => 'Second unread',
            'url' => '/memories',
            'type' => NotificationType::PROCESS->value,
        ]);

        $this->actingAs($user, 'api')
            ->patchJson('/api/notifications/read', [
                'ids' => [$first->id, $second->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $first->id,
                'seen' => true,
            ])
            ->assertJsonFragment([
                'id' => $second->id,
                'seen' => true,
            ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $first->id,
            'seen' => true,
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $second->id,
            'seen' => true,
        ]);
    }

    public function test_user_cannot_mark_other_users_notifications_as_read_in_batch(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user, 'api');

        $ownNotification = Notification::query()->create([
            'title' => 'Own notification',
            'url' => '/memories',
            'type' => NotificationType::DEFAULT->value,
        ]);

        $this->actingAs($otherUser, 'api');
        $otherNotification = Notification::query()->create([
            'title' => 'Other notification',
            'url' => '/memories',
            'type' => NotificationType::PROCESS->value,
        ]);

        $this->actingAs($user, 'api')
            ->patchJson('/api/notifications/read', [
                'ids' => [$ownNotification->id, $otherNotification->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids.1');

        $this->assertDatabaseHas('notifications', [
            'id' => $ownNotification->id,
            'seen' => false,
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $otherNotification->id,
            'seen' => false,
        ]);
    }
}
