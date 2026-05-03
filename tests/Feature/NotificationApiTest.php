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
            ])
            ->assertJsonMissing([
                'title' => 'Hidden notification',
            ]);
    }
}
