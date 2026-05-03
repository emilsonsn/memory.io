<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_user(): void
    {
        $plan = Plan::factory()->create();

        $this->postJson('/api/users', [
            'name' => 'Emilson',
            'email' => 'emilson@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan_id' => $plan->id,
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'name' => 'Emilson',
                'email' => 'emilson@example.com',
                'plan_id' => $plan->id,
            ])
            ->assertJsonMissingPath('data.password');

        $user = User::query()->where('email', 'emilson@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertSame($plan->id, $user->plan_id);
    }

    public function test_user_creation_validates_unique_email(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        $this->postJson('/api/users', [
            'name' => 'Taken',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_guest_cannot_list_users(): void
    {
        $this->getJson('/api/users')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_users(): void
    {
        $user = User::factory()->create([
            'name' => 'Visible User',
        ]);

        $this->actingAs($user)
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'name' => 'Visible User',
                'email' => $user->email,
            ]);
    }

    public function test_authenticated_user_can_show_update_and_delete_user(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create([
            'name' => 'Old Name',
        ]);

        $this->actingAs($user)
            ->getJson("/api/users/{$targetUser->id}")
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Old Name',
            ]);

        $this->actingAs($user)
            ->patchJson("/api/users/{$targetUser->id}", [
                'name' => 'New Name',
                'email' => 'new-name@example.com',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'New Name',
                'email' => 'new-name@example.com',
            ]);

        $this->actingAs($user)
            ->deleteJson("/api/users/{$targetUser->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }
}
