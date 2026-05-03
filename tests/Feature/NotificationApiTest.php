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
            'type' => NotificationType::Default,
        ]);

        $this->actingAs($otherUser, 'api');

        Notification::query()->create([
            'title' => 'Hidden notification',
            'url' => '/memories',
            'type' => NotificationType::Process,
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
                'type' => NotificationType::Default,
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
            'type' => NotificationType::Default,
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
}
