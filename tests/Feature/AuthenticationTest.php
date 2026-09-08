<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Authentication flow tests: login, registration, activation,
 * password reset authorization and brute-force protection.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function seedRoles(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
    }

    protected function makeUser(array $attributes = []): User
    {
        $this->seedRoles();

        $user = User::factory()->create(array_merge([
            'password'  => Hash::make('Secret@123!'),
            'activated' => 1,
            'status'    => 1,
            'trash'     => 0,
        ], $attributes));

        $user->assignRole('subscriber');

        return $user;
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign in', false);
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = $this->makeUser();

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'Secret@123!',
        ]);

        $this->assertAuthenticatedViaSession($user);
        $response->assertRedirect('/subscriber');
    }

    public function test_unactivated_accounts_cannot_login(): void
    {
        $user = $this->makeUser(['activated' => 0]);

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'Secret@123!',
        ]);

        $response->assertSessionHas('danger');
        $this->assertGuestSession();
    }

    public function test_invalid_credentials_are_rejected_with_generic_message(): void
    {
        $user = $this->makeUser();

        $response = $this->from('/login')->post('/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHas('danger', __('auth.failed'));
        $this->assertGuestSession();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = $this->makeUser();

        foreach (range(1, 5) as $i) {
            $this->post('/login', [
                'email'    => $user->email,
                'password' => 'definitely-wrong',
            ]);
        }

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'Secret@123!',
        ]);

        $this->assertTrue(in_array($response->status(), [302, 429]));
        $this->assertGuestSession();
    }

    public function test_registration_assigns_least_privileged_role(): void
    {
        \Mail::fake();

        $response = $this->post('/register', [
            'username'             => 'Jane Doe',
            'email'                => 'jane@example.com',
            'password'             => 'Sup3r$ecret',
            'password_confirmation'=> 'Sup3r$ecret',
        ]);

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('subscriber', $user->roleName());
        $this->assertSame(0, (int) $user->activated);
        $response->assertRedirect('/login');
    }

    public function test_password_update_requires_session_grant(): void
    {
        // CRITICAL regression guard: previously anyone could POST a new
        // password for any user id without ever presenting an email token.
        $user = $this->makeUser();

        $response = $this->post('/updatepassword', [
            'password'             => 'NewPass@123',
            'password_confirmation'=> 'NewPass@123',
        ]);

        $response->assertRedirect(route('forgotpassword'));

        $user->refresh();
        $this->assertTrue(Hash::check('Secret@123!', $user->password), 'Password must not change without a reset grant.');
        $this->assertFalse(Hash::check('NewPass@123', $user->password));
    }

    public function test_expired_remember_cookies_do_not_authenticate(): void
    {
        $this->seedRoles();

        $user = User::factory()->create([
            'password'  => Hash::make('Secret@123!'),
            'activated' => 1,
        ]);
        $user->assignRole('subscriber');

        \Illuminate\Support\Facades\DB::table('auth_tokens')->insert([
            'user_id'         => $user->id,
            'selector'        => 'expiredselector123',
            'hashedvalidator' => hash('sha256', 'validator123'),
            'token_type'      => 'remember_me',
            'expires_at'      => now()->subDay(),
            'created_at'      => now()->subDays(31),
            'updated_at'      => now()->subDays(31),
        ]);

        $response = $this->withUnencryptedCookie(
            'remember',
            'expiredselector123:validator123'
        )->get('/login');

        $this->assertGuestSession();
    }

    public function test_logout_requires_post(): void
    {
        $user = $this->makeUser();

        $this->actingAsSession($user);

        $this->get('/logout')->assertStatus(405);

        $this->post('/logout');
        $this->assertGuestSession();
    }

    /* ------------------------------------------------------------ helpers */

    protected function actingAsSession(User $user): self
    {
        session([
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->roleName(),
            'isLoggedIn' => true,
        ]);

        return $this;
    }

    protected function assertGuestSession(): void
    {
        $this->assertFalse((bool) session('isLoggedIn'), 'Session should not be authenticated.');
    }

    protected function assertAuthenticatedViaSession(User $user): void
    {
        $this->assertSame((int) session('id'), (int) $user->id);
        $this->assertTrue((bool) session('isLoggedIn'));
    }
}
