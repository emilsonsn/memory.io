<?php

namespace Tests\Unit;

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
