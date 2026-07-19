<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Memory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_admin_panel_is_rendered_in_brazilian_portuguese(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Faça login')
            ->assertSee('Senha')
            ->assertDontSee('Sign in');
    }

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(UserRole::USER->value);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_access_user_and_plan_management(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN->value);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/users/create')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/plans/create')
            ->assertOk();
    }

    public function test_user_listing_shows_memory_and_category_counts(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN->value);

        $user = User::factory()->create([
            'name' => 'Usuário com conteúdo',
        ]);

        Memory::factory()->for($user)->count(3)->create();
        Category::factory()->for($user)->count(2)->create();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Memórias')
            ->assertSee('Categorias')
            ->assertSee('Usuário com conteúdo')
            ->assertSee('3')
            ->assertSee('2');
    }

    public function test_panel_does_not_register_memory_or_category_resources(): void
    {
        $this->assertFalse(Route::has('filament.admin.resources.memories.index'));
        $this->assertFalse(Route::has('filament.admin.resources.categories.index'));
    }
}
