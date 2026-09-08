<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Mail\SendActivationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin', 'editor', 'subscriber'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    public function test_login_page_renders(): void
    {
        $this->get('/sysCtrlLogin')->assertStatus(200);
    }

    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_register_rejects_weak_password(): void
    {
        $response = $this->post('/register', [
            'username' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'weak',
            'terms' => 'on',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'john@example.com']);
    }

    public function test_register_assigns_subscriber_role_not_admin(): void
    {
        Mail::fake();

        $this->post('/register', [
            'username' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Strong@123',
            'terms' => 'on',
        ])->assertRedirect(route('login'));

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertEquals('subscriber', $user->roles);
        $this->assertTrue($user->hasRole('subscriber'));
        $this->assertFalse($user->hasRole('admin'));

        Mail::assertSent(SendActivationMail::class);
    }

    public function test_activation_link_activates_account(): void
    {
        Mail::fake();

        $this->post('/register', [
            'username' => 'Activ Acted',
            'email' => 'activate@example.com',
            'password' => 'Strong@123',
            'terms' => 'on',
        ])->assertRedirect(route('login'));

        $link = null;
        Mail::assertSent(SendActivationMail::class, function ($mail) use (&$link) {
            $link = $mail->activationLink;

            return true;
        });

        $path = parse_url($link, PHP_URL_PATH);
        $this->get($path)->assertRedirect(route('login'));

        $this->assertEquals(1, (int) User::where('email', 'activate@example.com')->firstOrFail()->activated);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = $this->makeUser();

        $this->post('/sysCtrlLogin', [
            'email' => $user->email,
            'password' => 'Wrong@123',
        ])->assertSessionHas('danger');

        $this->assertFalse(session()->has('isLoggedIn'));
    }

    public function test_login_succeeds_and_redirects_by_role(): void
    {
        $user = $this->makeUser(['roles' => 'admin']);
        $user->syncRoles(['admin']);

        $this->post('/sysCtrlLogin', [
            'email' => $user->email,
            'password' => 'Strong@123',
        ])->assertRedirect('/admin');

        $this->assertTrue(session()->has('isLoggedIn'));
        $this->assertEquals('admin', session('role'));
    }

    public function test_full_password_reset_flow(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $this->post('/forgotpassword', ['email' => $user->email])
            ->assertSessionHas('success');

        $link = null;
        Mail::assertSent(ResetPasswordMail::class, function ($mail) use (&$link) {
            $link = $mail->resetlink;

            return true;
        });

        // 1. Follow the e-mail link -> redirected to the update form.
        $path = parse_url($link, PHP_URL_PATH);
        $response = $this->get($path);
        $response->assertRedirect(route('password.update', ['id' => $user->id]));
        $this->assertEquals($user->id, session('password_reset_verified_id'));

        // 2. The form renders for the verified user.
        $this->get('/updatepassword/'.$user->id)->assertStatus(200);

        // 3. Submit the new password.
        $this->post('/updatepassword/'.$user->id, [
            'password' => 'BrandNew@456',
            'confirm-password' => 'BrandNew@456',
        ])->assertRedirect(route('login'));

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('BrandNew@456', $fresh->password));
        $this->assertNull($fresh->reset_token);
        $this->assertFalse(session()->has('password_reset_verified_id'));

        // 4. Login works with the new password.
        $this->post('/sysCtrlLogin', [
            'email' => $user->email,
            'password' => 'BrandNew@456',
        ])->assertRedirect('/subscriber');
    }

    public function test_password_update_rejected_without_verified_reset_link(): void
    {
        $victim = $this->makeUser(['email' => 'victim@example.com']);

        // Attacker guesses the numeric id and posts directly: must be refused.
        $this->post('/updatepassword/'.$victim->id, [
            'password' => 'Hacked@123',
            'confirm-password' => 'Hacked@123',
        ])->assertRedirect(route('login'));

        $this->assertFalse(Hash::check('Hacked@123', $victim->fresh()->password));
    }

    public function test_tampered_reset_token_is_rejected(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $this->post('/forgotpassword', ['email' => $user->email]);

        $link = null;
        Mail::assertSent(ResetPasswordMail::class, function ($mail) use (&$link) {
            $link = $mail->resetlink;

            return true;
        });

        $path = parse_url($link, PHP_URL_PATH);
        $tampered = substr($path, 0, -2).'xx';

        $this->get($tampered)->assertRedirect(route('login'));
        $this->assertFalse(session()->has('password_reset_verified_id'));
    }

    /**
     * Create an activated user (plaintext password is hashed by the model cast).
     */
    protected function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'Strong@123',
            'roles' => 'subscriber',
            'activated' => 1,
            'status' => 1,
            'trash' => 0,
        ], $overrides));
    }
}
