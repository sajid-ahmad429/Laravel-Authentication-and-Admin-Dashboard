<?php

namespace App\Libraries;

use App\Mail\ResetPasswordMail;
use App\Mail\SendActivationMail;
use App\Models\AuthToken;
use App\Models\AuthModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Authentication service (session based).
 *
 * Security properties implemented here:
 *  - Opaque activation / reset tokens: 48 chars of random entropy, only the
 *    SHA-256 hash is persisted; the plaintext token is never stored.
 *  - Reset grants are bound to the session (a password may only be set right
 *    after a valid token check) — the old flow allowed anyone to POST a new
 *    password for ANY user id without ever presenting a token.
 *  - Remember-me cookies use the selector/validator pattern with server side
 *    expiry validation (expiry was previously never checked) and the validator
 *    hash is rotated on every use (replay protection).
 *  - Session ID regeneration on login and cookie-based login (session fixation).
 */
class AuthLibrary
{
    protected AuthModel $authModel;

    public function __construct()
    {
        $this->authModel = new AuthModel();
    }

    /* ======================================================================
     |  LOGIN
     * ====================================================================== */

    /**
     * Attempt to authenticate and hydrate the session.
     * The caller has already verified credentials + activation status.
     */
    public function login(User $user, bool $remember = false): void
    {
        // Prevent session fixation: new id before privilege change.
        session()->regenerate();

        $this->setUserSession($user);

        if ($remember && config('auth.rememberMe.enabled')) {
            $this->rememberMe($user->id);
            session(['rememberme' => true]);
        } else {
            session()->forget('rememberme');
        }

        session(['lockscreen' => false]);

        $this->logLoginSuccess($user);
    }

    /**
     * Log a failed authentication attempt.
     */
    public function logLoginFailure(?User $user, string $email, string $reason = 'Invalid credentials'): void
    {
        try {
            $this->authModel->logLogin([
                'user_id'        => $user->id ?? null,
                'name'           => $user->name ?? null,
                'email'          => $email,
                'role'           => $user?->getRoleNames()->first(),
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
                'device_type'    => $this->detectDevice(request()),
                'successful'     => false,
                'failure_reason' => $reason,
                'logged_in_at'   => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to persist login failure log: '.$e->getMessage());
        }
    }

    protected function logLoginSuccess(User $user): void
    {
        try {
            $this->authModel->logLogin([
                'user_id'        => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'role'           => $user->getRoleNames()->first(),
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
                'device_type'    => $this->detectDevice(request()),
                'successful'     => true,
                'failure_reason' => null,
                'logged_in_at'   => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to persist login log: '.$e->getMessage());
        }
    }

    protected function detectDevice(Request $request): string
    {
        $agent = strtolower((string) $request->userAgent());

        return match (true) {
            str_contains($agent, 'mobile') || str_contains($agent, 'android') || str_contains($agent, 'iphone') => 'Mobile',
            str_contains($agent, 'tablet') || str_contains($agent, 'ipad') => 'Tablet',
            $agent === '' => 'Unknown',
            default => 'Desktop',
        };
    }

    /* ======================================================================
     |  SESSION
     * ====================================================================== */

    /**
     * Persist the authenticated identity in the session.
     */
    public function setUserSession(User $user): void
    {
        $spatieRole = null;

        try {
            $spatieRole = $user->getRoleNames()->first();
        } catch (Throwable) {
            // Spatie not initialised (rare, e.g. missing tables) — fall back below.
        }

        session([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => strtolower($spatieRole ?? config('auth.default_role')),
            'isLoggedIn'  => true,
            'ipaddress'   => request()->ip(),
            'lastLoginAt' => now()->toDateTimeString(),
            'passwordHash'=> $user->password, // enables invalidation when password changes
        ]);
    }

    /**
     * Role aware post-login redirect target.
     */
    public function autoRedirect(): string
    {
        $role    = strtolower((string) session('role'));
        $targets = (array) config('auth.assign_redirect');

        return $targets[$role] ?? '/login';
    }

    /**
     * Terminate the session completely (also used on forced invalidation).
     */
    public function logout(): void
    {
        if ($userId = session('id')) {
            try {
                $this->authModel->deleteTokenByUserId((int) $userId);
            } catch (Throwable $e) {
                Log::warning('Failed deleting remember tokens on logout: '.$e->getMessage());
            }
        }

        Cookie::queue(Cookie::forget('remember'));

        Session::flush();
        session()->invalidate();
        session()->regenerateToken();
    }

    /* ======================================================================
     |  REGISTRATION & ACTIVATION
     * ====================================================================== */

    /**
     * Create a new account from public registration.
     *
     * @return User|false
     */
    public function registerUser(array $userData): User|false
    {
        try {
            $user = new User();
            $user->name      = $userData['name'];
            $user->email     = $userData['email'];
            $user->password  = $userData['password']; // hashed by the model cast
            $user->status    = 1;
            $user->trash     = 0;
            $user->activated = 0;
            $user->save();

            // Assign the Spatie role (least privilege).
            $user->assignRole(config('auth.default_role', 'subscriber'));
            $user->refresh();

            if (config('auth.send_activation_email', true)) {
                $token = $this->issueToken($user, 'activate_token');

                if (! $this->sendActivationEmail($user, $token)) {
                    // Account exists; the user can request a new link.
                    Log::error('Activation email could not be queued.', ['user_id' => $user->id]);
                }
            } else {
                $user->forceFill(['activated' => 1])->save();
            }

            return $user;
        } catch (Throwable $e) {
            Log::error('Registration failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Re-issue and email an activation link for an unactivated account.
     *
     * @return bool Always true when the request was accepted. The response is
     *              intentionally identical whether or not the email exists to
     *              prevent account enumeration.
     */
    public function resendActivation(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if (! $user || (int) $user->activated === 1) {
            return true; // silently ignore — no enumeration
        }

        $token = $this->issueToken($user, 'activate_token');

        return $this->sendActivationEmail($user, $token);
    }

    /**
     * Build + dispatch the activation mail. The mailable implements
     * ShouldQueue so delivery happens on the queue.
     */
    public function sendActivationEmail(User $user, string $token): bool
    {
        try {
            Mail::to($user->email)->send(
                new SendActivationMail($user, $this->activationUrl($user->id, $token))
            );

            return true;
        } catch (Throwable $e) {
            Log::error('Activation email dispatch failed: '.$e->getMessage());

            return false;
        }
    }

    protected function activationUrl(int|string $id, string $token): string
    {
        return url('/activate/'.base64_encode((string) $id).'/'.$token);
    }

    /**
     * Consume an activation token and activate the account.
     *
     * @return array{ok: bool, message: string}
     */
    public function activateUser(string $encodedId, string $token): array
    {
        $id = base64_decode($encodedId, true);

        if ($id === false || ! ctype_digit($id)) {
            return ['ok' => false, 'message' => __('auth.noAuth')];
        }

        $user = User::find((int) $id);

        if (! $user) {
            return ['ok' => false, 'message' => __('auth.noUser')];
        }

        if ((int) $user->activated === 1) {
            return ['ok' => true, 'message' => __('auth.accountAlreadyActivated')];
        }

        if (! $this->tokenIsValid($user->activate_token, $user->activate_expire, $token)) {
            return ['ok' => false, 'message' => __('auth.linkExpired')];
        }

        $user->forceFill([
            'activated'       => 1,
            'activate_token'  => null,
            'activate_expire' => null,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return ['ok' => true, 'message' => __('auth.accountActivated')];
    }

    /* ======================================================================
     |  PASSWORD RESET
     * ====================================================================== */

    /**
     * Issue a reset link for the given account (rate limiting handled at route).
     *
     * @return bool true when a mail was queued.
     */
    public function sendResetLink(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return false;
        }

        $token = $this->issueToken($user, 'reset_token');

        try {
            Mail::to($user->email)->send(new ResetPasswordMail(
                $user,
                url('/resetpassword/'.base64_encode((string) $user->id).'/'.$token)
            ));

            return true;
        } catch (Throwable $e) {
            Log::error('Reset email dispatch failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Validate the reset token presented in the email link.
     *
     * @return int|null Authenticated user id when valid, null otherwise.
     */
    public function validateResetToken(string $encodedId, string $token): ?int
    {
        $id = base64_decode($encodedId, true);

        if ($id === false || ! ctype_digit($id)) {
            return null;
        }

        $user = User::find((int) $id);

        if (! $user) {
            return null;
        }

        if (! $this->tokenIsValid($user->reset_token, $user->reset_expire, $token)) {
            return null;
        }

        return (int) $user->id;
    }

    /**
     * Store a one-time, session bound grant after a valid reset token check.
     * The grant is what authorises the subsequent POST to set a new password.
     */
    public function grantPasswordReset(int $userId): void
    {
        session([
            'password_reset_grant' => [
                'user_id'   => $userId,
                'expires_at' => now()->addMinutes(15)->getTimestamp(),
            ],
        ]);
    }

    /**
     * Consume the session bound grant (single use).
     */
    public function consumePasswordResetGrant(int $userId): bool
    {
        $grant = session('password_reset_grant');

        if (! is_array($grant)
            || (int) ($grant['user_id'] ?? 0) !== $userId
            || now()->getTimestamp() > (int) ($grant['expires_at'] ?? 0)) {
            return false;
        }

        session()->forget('password_reset_grant');

        return true;
    }

    /**
     * Apply the new password (called only after grant verification).
     */
    public function applyNewPassword(int $userId, string $password): bool
    {
        $user = User::find($userId);

        if (! $user) {
            return false;
        }

        $user->forceFill([
            'password'      => $password, // hashed by model cast
            'reset_token'   => null,
            'reset_expire'  => null,
        ])->save();

        // Invalidate every remembered device for this account.
        $this->authModel->deleteTokenByUserId($userId);

        return true;
    }

    /* ======================================================================
     |  TOKEN PLUMBING
     * ====================================================================== */

    /**
     * Generate a cryptographically random token, persist ONLY its hash and
     * return the plaintext for inclusion in the emailed link.
     *
     * @param  User   $user
     * @param  string $type 'activate_token'|'reset_token'
     */
    public function issueToken(User $user, string $type): string
    {
        $expireColumn = match ($type) {
            'activate_token' => 'activate_expire',
            'reset_token'    => 'reset_expire',
            default          => throw new InvalidArgumentException('Invalid token type provided.'),
        };

        $hours  = (int) config("auth.{$type}_expire", 24);
        $plain  = Str::random(48);

        $user->forceFill([
            $type        => hash('sha256', $plain),
            $expireColumn => now()->addHours(max(1, $hours)),
        ])->save();

        return $plain;
    }

    /**
     * Constant-time verification of a plaintext token against its stored
     * hash, including expiry handling.
     */
    protected function tokenIsValid(?string $storedHash, $expiry, string $plaintext): bool
    {
        if (empty($storedHash) || ! is_string($plaintext) || $plaintext === '') {
            return false;
        }

        if ($expiry && now()->gte(Carbon::parse($expiry))) {
            return false;
        }

        return hash_equals($storedHash, hash('sha256', $plaintext));
    }

    /* ======================================================================
     |  REMEMBER ME (selector/validator cookie)
     * ====================================================================== */

    public function rememberMe(int $userId): void
    {
        if (! config('auth.rememberMe.enabled')) {
            return;
        }

        $selector = Str::random(24);
        $validator = Str::random(32);
        $expires   = now()->addDays((int) config('auth.rememberMe.expire_days', 30));

        $data = [
            'user_id'         => $userId,
            'selector'        => $selector,
            'hashedvalidator' => hash('sha256', $validator),
            'token_type'      => 'remember_me',
            'expires'         => $expires,
        ];

        $existing = $this->authModel->getAuthTokenByUserId($userId);

        if (empty($existing)) {
            $this->authModel->insertToken($data);
        } else {
            $this->authModel->updateToken($data);
        }

        Cookie::queue(
            'remember',
            $selector.':'.$validator,
            (int) max(1, now()->diffInMinutes($expires)),
            '/',
            config('session.domain'),
            config('session.secure', false),
            true,
            false,
            config('session.same_site', 'lax')
        );
    }

    /**
     * Attempt authentication via the remember-me cookie.
     * Fails closed on malformed, expired or replayed cookies.
     */
    public function checkCookie(): void
    {
        if (Session::get('lockscreen')) {
            return;
        }

        $remember = Cookie::get('remember');

        if (! is_string($remember) || substr_count($remember, ':') !== 1) {
            return;
        }

        [$selector, $validator] = explode(':', $remember, 2);

        if ($selector === '' || $validator === '') {
            return;
        }

        $token = AuthToken::where('selector', $selector)->first();

        if (! $token || ! hash_equals((string) $token->hashedvalidator, hash('sha256', $validator))) {
            return;
        }

        // Expired remember cookies must never authenticate.
        if ($token->expires_at && now()->gt($token->expires_at)) {
            $this->authModel->deleteTokenByUserId((int) $token->user_id);

            return;
        }

        $user = User::find($token->user_id);

        if (! $user || (int) $user->activated !== 1 || (int) $user->status !== 1 || (int) $user->trash !== 0) {
            $this->authModel->deleteTokenByUserId((int) $token->user_id);

            return;
        }

        // Rotate the validator (replay protection) and open the session.
        $this->login($user, true);
    }
}
