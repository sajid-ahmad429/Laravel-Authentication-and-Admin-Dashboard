<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Role-based access control tests.
 *
 * Regression guards for the historical bug where EVERY authenticated role
 * (including fresh subscribers) could reach every admin page and data API.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function seedRoles(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
    }

    protected function loginAs(string $role, array $attributes = []): User
    {
        $this->seedRoles();

        $user = User::factory()->create(array_merge([
            'password'  => Hash::make('Secret@123!'),
            'activated' => 1,
            'status'    => 1,
            'trash'     => 0,
        ], $attributes));

        $user->assignRole($role);

        session([
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $role,
            'isLoggedIn' => true,
        ]);

        return $user;
    }

    public function test_subscriber_cannot_open_admin_users_page(): void
    {
        $this->loginAs('subscriber');

        $this->get('/subscriber/users')
            ->assertRedirect('/subscriber');
    }

    public function test_subscriber_cannot_call_user_data_api(): void
    {
        $this->loginAs('subscriber');

        $this->post('/subscriber/users/data', [])
            ->assertStatus(403);
    }

    public function test_editor_cannot_manage_roles(): void
    {
        $this->loginAs('editor');

        $this->get('/editor/roles')->assertRedirect('/editor');
        $this->post('/editor/roles/data', [])->assertStatus(403);
    }

    public function test_admin_can_manage_users_and_roles(): void
    {
        $this->loginAs('admin');

        $this->get('/admin/users')->assertOk();
        $this->get('/admin/roles')->assertOk();
        $this->post('/admin/users/data')->assertOk();
    }

    public function test_admin_cannot_enter_other_role_panels(): void
    {
        $this->loginAs('admin');

        $this->get('/superadmin/users')->assertRedirect('/admin');
        $this->get('/editor/users')->assertRedirect('/admin');
    }

    public function test_superadmin_can_preview_all_panels(): void
    {
        $this->loginAs('superadmin');

        $this->get('/superadmin/users')->assertOk();
        $this->get('/admin/users')->assertOk();
        $this->get('/subscriber/users')->assertOk();
    }

    public function test_unknown_panels_are_not_routable(): void
    {
        $this->loginAs('superadmin');

        $this->get('/hacker/users')->assertNotFound();
    }

    public function test_admin_cannot_trash_a_superadmin_account(): void
    {
        session()->flush();

        $actor = $this->loginAs('admin');
        $target = User::factory()->create(['activated' => 1]);
        $target->assignRole('superadmin');

        $this->post('/admin/users/trash-toggle', [
            'id'          => $target->id,
            'action_type' => 1,
        ])->assertStatus(403);

        $this->assertSame(0, (int) $target->fresh()->trash);
    }

    public function test_admin_cannot_assign_superadmin_role(): void
    {
        $this->loginAs('admin');

        $this->post('/admin/users/store', [
            'userFullname' => 'Escalated User',
            'userEmail'    => 'escalated@example.com',
            'user-role'    => 'superadmin',
        ])->assertStatus(422);

        $this->assertNull(User::where('email', 'escalated@example.com')->first());
    }

    public function test_user_cannot_trash_their_own_account(): void
    {
        $user = $this->loginAs('admin');

        $this->post('/admin/users/trash-toggle', [
            'id'          => $user->id,
            'action_type' => 1,
        ])->assertStatus(403);

        $this->assertSame(0, (int) $user->fresh()->trash);
    }

    public function test_unauthenticated_data_api_returns_401_json(): void
    {
        $this->post('/admin/users/data')->assertStatus(401);
    }

    public function test_status_endpoint_rejects_unknown_values(): void
    {
        $actor = $this->loginAs('admin');
        $target = User::factory()->create(['activated' => 1]);
        $target->assignRole('subscriber');

        $this->post('/admin/users/status', [
            'id'     => $target->id,
            'status' => 7,
        ])->assertStatus(422);

        $this->post('/admin/users/status', [
            'id'     => $target->id,
            'status' => 0,
        ])->assertOk();

        $this->assertSame(0, (int) $target->fresh()->status);
    }
}
