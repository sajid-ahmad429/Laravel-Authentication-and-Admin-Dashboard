<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin', 'editor', 'subscriber'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    public function test_guests_are_redirected_from_admin_pages(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/users')->assertRedirect(route('login'));
        $this->get('/admin/roles')->assertRedirect(route('login'));
        $this->get('/admin/activity-logs')->assertRedirect(route('login'));
        $this->get('/admin/health')->assertRedirect(route('login'));
    }

    public function test_guests_cannot_mutate_users_or_status(): void
    {
        $this->post('/admin/users/persistence-store', [])->assertRedirect(route('login'));
        $this->post('/admin/users/trash-toggle', [])->assertRedirect(route('login'));
        $this->post('/chnage_status', [])->assertRedirect(route('login'));
        $this->postJson('/admin/users/persistence-store', [])->assertStatus(401);
    }

    public function test_logout_requires_post(): void
    {
        $this->get('/logout')->assertStatus(405);
    }

    public function test_admin_can_view_user_list(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAsSession($admin, 'admin')
            ->get('/admin/users')
            ->assertStatus(200);
    }

    public function test_editor_cannot_create_users(): void
    {
        $editor = $this->makeUser(['email' => 'editor@example.com', 'roles' => 'editor']);
        $editor->syncRoles(['editor']);

        $this->actingAsSession($editor, 'editor')
            ->postJson('/admin/users/persistence-store', [
                'userFullname' => 'New Guy',
                'userEmail' => 'newguy@example.com',
                'userContact' => '1234567890',
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('users', ['email' => 'newguy@example.com']);
    }

    public function test_admin_can_create_user_with_random_password(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAsSession($admin, 'admin')
            ->postJson('/admin/users/persistence-store', [
                'userFullname' => 'New Guy',
                'userEmail' => 'newguy@example.com',
                'userContact' => '1234567890',
                'user-role' => 'editor',
            ]);

        $response->assertOk()->assertJson(['status' => 1]);
        $this->assertNotEmpty($response->json('temp_password'));

        $created = User::where('email', 'newguy@example.com')->firstOrFail();
        $this->assertEquals(1, (int) $created->activated);
        $this->assertTrue($created->hasRole('editor'));
    }

    public function test_admin_cannot_grant_superadmin_role(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAsSession($admin, 'admin')
            ->postJson('/admin/users/persistence-store', [
                'userFullname' => 'Sneaky Pete',
                'userEmail' => 'sneaky@example.com',
                'userContact' => '1234567891',
                'user-role' => 'superadmin',
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_status_change_rejects_unknown_tables(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAsSession($admin, 'admin')
            ->postJson('/chnage_status', [
                'id' => base64_encode((string) $admin->id),
                'status' => 0,
                'name' => base64_encode('sessions'),
            ])
            ->assertStatus(403);
    }

    public function test_users_cannot_trash_themselves(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAsSession($admin, 'admin')
            ->postJson('/admin/users/trash-toggle', [
                'id' => base64_encode((string) $admin->id),
                'action_type' => 1,
            ])
            ->assertStatus(403);

        $this->assertEquals(0, (int) $admin->fresh()->trash);
    }

    protected function makeAdmin(): User
    {
        $admin = $this->makeUser(['email' => 'admin@example.com', 'roles' => 'admin']);
        $admin->syncRoles(['admin']);

        return $admin;
    }

    protected function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'Strong@123',
            'roles' => 'subscriber',
            'activated' => 1,
            'status' => 1,
            'trash' => 0,
        ], $overrides));
    }

    /**
     * Simulate the custom session login used by AuthLibrary.
     */
    protected function actingAsSession(User $user, string $role): static
    {
        return $this->withSession([
            'isLoggedIn' => true,
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
        ]);
    }
}
