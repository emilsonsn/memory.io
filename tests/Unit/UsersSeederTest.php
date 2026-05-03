<?php

namespace Tests\Unit;

use App\Models\User;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_default_users_with_roles_and_plans(): void
    {
        $this->seed(PlansSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(UsersSeeder::class);

        $admin = User::where('email', 'admin@memory.io')->firstOrFail();
        $basicUser = User::where('email', 'basic@memory.io')->firstOrFail();
        $premiumUser = User::where('email', 'premium@memory.io')->firstOrFail();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($basicUser->hasRole('user'));
        $this->assertTrue($premiumUser->hasRole('user'));
        $this->assertSame('Basic', $basicUser->plan->name);
        $this->assertSame('Premium', $premiumUser->plan->name);
    }
}
