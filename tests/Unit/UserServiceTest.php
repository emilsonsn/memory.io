<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_user_with_hashed_password(): void
    {
        $user = app(UserService::class)->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $this->assertSame('Jane Doe', $user->name);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_it_updates_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Before',
        ]);

        $updatedUser = app(UserService::class)
            ->setUser($user)
            ->update([
                'name' => 'After',
            ]);

        $this->assertSame('After', $updatedUser->name);
    }

    public function test_it_does_not_update_plan_with_regular_update(): void
    {
        $currentPlan = Plan::factory()->create();
        $newPlan = Plan::factory()->create();
        $user = User::factory()->create([
            'plan_id' => $currentPlan->id,
        ]);

        $updatedUser = app(UserService::class)
            ->setUser($user)
            ->update([
                'name' => 'After',
                'plan_id' => $newPlan->id,
            ]);

        $this->assertSame('After', $updatedUser->name);
        $this->assertSame($currentPlan->id, $updatedUser->plan_id);
    }

    public function test_it_assigns_plan_explicitly_for_webhook_flow(): void
    {
        $plan = Plan::factory()->create();
        $user = User::factory()->create([
            'plan_id' => null,
        ]);

        $updatedUser = app(UserService::class)
            ->setUser($user)
            ->assignPlan($plan);

        $this->assertSame($plan->id, $updatedUser->plan_id);
    }

    public function test_it_deletes_user(): void
    {
        $user = User::factory()->create();

        app(UserService::class)
            ->setUser($user)
            ->delete();

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
