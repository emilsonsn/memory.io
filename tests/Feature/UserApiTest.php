<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_user(): void
    {
        $this->postJson('/api/users', [
            'name' => 'Emilson',
            'email' => 'emilson@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'name' => 'Emilson',
                'email' => 'emilson@example.com',
            ])
            ->assertJsonMissingPath('data.password');

        $user = User::query()->where('email', 'emilson@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password123', $user->password));
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

    public function test_regular_user_cannot_list_users(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)
            ->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_authenticated_user_can_list_users(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'name' => 'Visible User',
        ]);
        $user->assignRole('admin');

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
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');
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

    public function test_user_plan_cannot_be_updated_through_user_endpoint(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $currentPlan = Plan::factory()->create();
        $newPlan = Plan::factory()->create();
        $targetUser = User::factory()->create([
            'plan_id' => $currentPlan->id,
        ]);

        $this->actingAs($user)
            ->patchJson("/api/users/{$targetUser->id}", [
                'name' => 'Still allowed',
                'plan_id' => $newPlan->id,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Still allowed',
                'plan_id' => $currentPlan->id,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'plan_id' => $currentPlan->id,
        ]);
    }
}
