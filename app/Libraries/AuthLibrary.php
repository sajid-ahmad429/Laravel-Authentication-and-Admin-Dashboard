<?php

/**
 * --------------------------------------------------------------------
 * LARAVEL - AuthLibrary
 * --------------------------------------------------------------------
 *
 * This content is released under the MIT License (MIT)
 *
 * Custom session-based authentication library with secure token handling,
 * remember-me cookies (selector + hashed validator pattern) and login auditing.
 */

namespace App\Libraries;

use App\Jobs\SendWelcomeEmail;
use App\Mail\ResetPasswordMail;
use App\Mail\SendActivationMail;
use App\Models\AuthModel;
use App\Models\AuthToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * AuthLibrary - Custom Authentication Library
 */
class AuthLibrary
{
    protected AuthModel $authModel;
    protected array $config;

    /**
     * Constructor
     *
     * Initialize the necessary services and models.
     */
    public function __construct()
    {
        $this->authModel = new AuthModel();
        $this->config = config('auth');
    }

    /*
     * --------------------------------------------------------------------------
     * Generate Token
     * --------------------------------------------------------------------------
     *
     * Generates a cryptographically secure URL-safe token, stores only its
     * hash in the database and returns the raw token for e-mail links.
     * The raw token is never persisted, so a database leak cannot be used
     * to hijack activation / password-reset links.
     *
     * @param  \App\Models\User|\App\Models\AuthModel  $user
     * @param  string  $tokenType  reset_token|activate_token
     * @return string  $rawToken (URL-safe, place directly in links)
     */
    public function generateToken($user, string $tokenType): string
    {
        if (! $user || ! $user->exists) {
            throw new InvalidArgumentException('Cannot generate a token for a missing user.');
        }

        // Str::random() is alphanumeric only, hence URL-safe without encoding.
        $token = Str::random(60);
        $hashedToken = Hash::make($token);

        if ($tokenType === 'reset_token') {
            $expiryColumn = 'reset_expire';
            $expireHours = (int) config('auth.reset_token_expire', 1);
        } elseif ($tokenType === 'activate_token') {
            $expiryColumn = 'activate_expire';
            $expireHours = (int) config('auth.activate_token_expire', 24);
        } else {
            throw new InvalidArgumentException('Invalid token type provided.');
        }

        $user->forceFill([
            $tokenType => $hashedToken,
            $expiryColumn => Carbon::now()->addHours(max($expireHours, 1)),
        ])->save();

        return $token;
    }

    /**
     * --------------------------------------------------------------------------
     * LOGIN USER
     * --------------------------------------------------------------------------
     *
     * Form validation is done in the controller. This method fetches the user,
     * verifies the account is activated, establishes the session and redirects
     * to the role-appropriate dashboard.
     *
     * @param  string  $email
     * @param  bool  $rememberMe
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginUser(string $email, bool $rememberMe = false)
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            session()->flash('danger', __('auth.failed'));
            return redirect()->back();
        }

        if ((int) $user->activated !== 1) {
            session()->flash('danger', __('Your account is not activated. Please check your e-mail for the activation link.'));
            session()->flash('resetlink', '<a href="'.route('resend.activation', $user->id).'">Resend Activation Email</a>');
            return redirect()->back();
        }

        $rememberConfig = config('auth.rememberMe', []);

        if (! empty($rememberConfig['enabled']) && $rememberMe) {
            $this->rememberMe($user->id);
            session(['rememberme' => '1']);
        }

        session(['lockscreen' => false]);

        $this->setUserSession($user);

        return redirect()->to($this->autoRedirect())->with('success', 'Login successful!');
    }

    /**
     * --------------------------------------------------------------------------
     * REGISTER USER
     * --------------------------------------------------------------------------
     *
     * Saves user details to the database, assigns the default (low-privilege)
     * role and optionally sends the activation e-mail.
     *
     * @param  array  $userData
     * @return bool
     */
    public function registerUser(array $userData): bool
    {
        $defaultRole = strtolower((string) config('auth.default_role', 'subscriber'));
        $userData['roles'] = $defaultRole;

        AuthModel::create($userData);

        $user = User::where('email', $userData['email'])->first();

        if (! $user) {
            session()->flash('danger', __('auth.error_occurred'));
            return false;
        }

        // Mirror the default role into Spatie so session/permission checks agree.
        try {
            $user->syncRoles([$defaultRole]);
        } catch (\Throwable $e) {
            Log::warning('Could not sync default Spatie role during registration: '.$e->getMessage());
        }

        if (config('auth.send_activation_email', true)) {
            $token = $this->generateToken($user, 'activate_token');

            if ($this->sendActivationEmail($user, $token)) {
                session()->flash('success', __('auth.account_created'));
                return true;
            }

            session()->flash('danger', __('auth.error_occurred'));
            return false;
        }

        $user->forceFill(['activated' => 1])->save();

        session()->flash('success', __('auth.account_created_no_auth'));
        return true;
    }

    /**
     * --------------------------------------------------------------------------
     * ACTIVATE EMAIL
     * --------------------------------------------------------------------------
     *
     * Sends the account activation e-mail with a signed link.
     *
     * @param  \App\Models\User|\App\Models\AuthModel  $user
     * @param  string  $activationToken  Raw (URL-safe) token
     * @return bool
     */
    public function sendActivationEmail($user, string $activationToken): bool
    {
        $encodedId = self::encodeId($user->id);
        $activationLink = url('/activate/'.$encodedId.'/'.$activationToken);

        try {
            Mail::to($user->email)->send(new SendActivationMail($user, $activationLink));

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send activation e-mail: '.$e->getMessage(), ['user_id' => $user->id ?? null]);
            session()->flash('danger', __('auth.error_occurred'));

            return false;
        }
    }

    /**
     * --------------------------------------------------------------------------
     * RESEND ACTIVATION EMAIL
     * --------------------------------------------------------------------------
     *
     * @param  mixed  $id
     * @return bool
     */
    public function resendActivation($id): bool
    {
        if (! is_numeric($id)) {
            session()->flash('danger', __('auth.error_occurred'));
            return false;
        }

        $user = User::find($id);

        if (! $user) {
            session()->flash('danger', __('auth.userNotFound'));
            return false;
        }

        if ((int) $user->activated === 1) {
            session()->flash('success', __('auth.account_activated'));
            return false;
        }

        $token = $this->generateToken($user, 'activate_token');

        if ($this->sendActivationEmail($user, $token)) {
            session()->flash('success', __('Activation email re-sent successfully.'));
            return true;
        }

        session()->flash('danger', __('An error occurred while sending the email.'));
        return false;
    }

    /**
     * --------------------------------------------------------------------------
     * ACTIVATE USER
     * --------------------------------------------------------------------------
     *
     * Validates an activation link (user id + token), checks expiry and
     * activates the account. Never throws for bad links; returns false.
     *
     * @param  string  $id     URL-safe base64 encoded user id
     * @param  string  $token  Raw token from the e-mail link
     * @return bool
     */
    public function activateUser($id, $token): bool
    {
        $decodedId = self::decodeId($id);

        if ($decodedId === null || empty($token) || ! is_string($token)) {
            Session::flash('danger', Lang::get('auth.invalidToken'));
            return false;
        }

        $user = AuthModel::find($decodedId);

        if (! $user || empty($user->activate_token)) {
            Session::flash('danger', Lang::get('auth.invalidToken'));
            return false;
        }

        if (empty($user->activate_expire) || Carbon::now()->greaterThanOrEqualTo(Carbon::parse($user->activate_expire))) {
            Session::flash('danger', Lang::get('auth.linkExpired'));
            return false;
        }

        if (! Hash::check($token, $user->activate_token)) {
            Session::flash('danger', Lang::get('auth.invalidToken'));
            return false;
        }

        $user->forceFill([
            'activated' => 1,
            'activate_token' => null,
            'activate_expire' => null,
        ])->save();

        Session::flash('success', Lang::get('auth.account_activated'));
        return true;
    }

    /**
     * --------------------------------------------------------------------------
     * FORGOT PASSWORD
     * --------------------------------------------------------------------------
     *
     * Generates a reset token and e-mails the reset link. Returns false when
     * the e-mail address is unknown (callers should still show a generic
     * success message to avoid account enumeration).
     *
     * @param  string  $email
     * @return bool
     */
    public function forgotPassword(string $email): bool
    {
        $user = AuthModel::where('email', $email)->first();

        if (! $user) {
            return false;
        }

        $token = $this->generateToken($user, 'reset_token');

        return $this->resetEmail($user, $token);
    }

    /**
     * --------------------------------------------------------------------------
     * RESET EMAIL
     * --------------------------------------------------------------------------
     *
     * Sends the user a password reset link e-mail.
     *
     * @param  \App\Models\User|\App\Models\AuthModel  $user
     * @param  string  $token  Raw (URL-safe) token
     * @return bool
     */
    public function resetEmail($user, string $token): bool
    {
        $encodedId = self::encodeId($user->id);
        $resetLink = url('/resetpassword/'.$encodedId.'/'.$token);

        try {
            Mail::to($user->email)->send(new ResetPasswordMail($user, $resetLink));

            session()->flash('success', __('auth.resetSent'));
            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send password reset e-mail: '.$e->getMessage(), ['user_id' => $user->id ?? null]);
            session()->flash('danger', __('auth.error_occurred'));
            return false;
        }
    }

    /**
     * --------------------------------------------------------------------------
     * RESET PASSWORD (verify link)
     * --------------------------------------------------------------------------
     *
     * Validates an incoming password-reset link. Returns the numeric user id
     * when the link is valid, or false otherwise (never throws for bad links).
     *
     * @param  string  $id     URL-safe base64 encoded user id
     * @param  string  $token  Raw token from the e-mail link
     * @return int|false
     */
    public function resetPassword($id, $token)
    {
        $decodedId = self::decodeId($id);

        if ($decodedId === null || empty($token) || ! is_string($token)) {
            Session::flash('danger', Lang::get('auth.invalidToken'));
            return false;
        }

        $user = AuthModel::find($decodedId);

        if (! $user) {
            Session::flash('danger', Lang::get('auth.userNotFound'));
            return false;
        }

        if (empty($user->reset_expire) || Carbon::now()->greaterThanOrEqualTo(Carbon::parse($user->reset_expire))) {
            Session::flash('danger', Lang::get('auth.linkExpired'));
            return false;
        }

        if (empty($user->reset_token) || ! Hash::check($token, $user->reset_token)) {
            Session::flash('danger', Lang::get('auth.noAuth'));
            return false;
        }

        Session::flash('success', Lang::get('auth.passwordAuthorised'));
        return (int) $user->id;
    }

    /**
     * --------------------------------------------------------------------------
     * SET USER SESSION
     * --------------------------------------------------------------------------
     *
     * Establishes the authenticated session: regenerates the session id
     * (session fixation protection), stores the identity payload and bridges
     * the login into Laravel's Auth guard so Auth::user(), @auth, policies
     * and Spatie permission checks all work.
     *
     * @param  \App\Models\User|\App\Models\AuthModel  $user
     * @return bool
     */
    public function setUserSession($user): bool
    {
        if (! $user) {
            return false;
        }

        $authUser = $user instanceof Authenticatable ? $user : User::find($user->id);

        $roleName = 'subscriber';
        if ($authUser) {
            try {
                $roleName = strtolower((string) ($authUser->getRoleNames()->first() ?? $user->roles ?? 'subscriber'));
            } catch (\Throwable $e) {
                $roleName = strtolower((string) ($user->roles ?? 'subscriber'));
            }
        }

        // Prevent session fixation attacks.
        request()->session()->regenerate();

        session([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $roleName ?: 'subscriber',
            'isLoggedIn' => true,
            'ipaddress' => request()->ip(),
        ]);

        // Bridge into Laravel's guard (enables Auth::check(), @auth, Spatie, ...).
        if ($authUser) {
            Auth::login($authUser);
        }

        $this->loginlog();

        return true;
    }

    /**
     * --------------------------------------------------------------------------
     * LOG LOGIN
     * --------------------------------------------------------------------------
     *
     * Logs a successful login session to the database.
     *
     * @return void
     */
    public function loginlog(): void
    {
        if (! session()->has('isLoggedIn')) {
            return;
        }

        try {
            $this->authModel->logLogin([
                'user_id' => session()->get('id'),
                'name' => session()->get('name'),
                'email' => session()->get('email'),
                'role' => session()->get('role'),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'device_type' => 'Desktop',
                'successful' => true,
                'failure_reason' => null,
                'logged_in_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to write login audit record: '.$e->getMessage());
        }
    }

    /**
     * --------------------------------------------------------------------------
     * LOG LOGIN FAILURE
     * --------------------------------------------------------------------------
     *
     * Logs an unsuccessful login attempt for an existing account.
     *
     * @param  string  $email
     * @param  string  $reason
     * @return void
     */
    public function loginlogFail(string $email, string $reason = 'Invalid credentials'): void
    {
        try {
            $user = AuthModel::where('email', $email)->first();

            if (! $user) {
                return;
            }

            $this->authModel->logLogin([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => is_string($user->roles) ? $user->roles : null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'device_type' => 'Desktop',
                'successful' => false,
                'failure_reason' => $reason,
                'logged_in_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to write failed-login audit record: '.$e->getMessage());
        }
    }

    /**
     * --------------------------------------------------------------------------
     * REMEMBER ME
     * --------------------------------------------------------------------------
     *
     * Issues a persistent login cookie using the secure selector:validator
     * pattern. Only the selector and a SHA-256 hash of the validator are
     * stored server-side; the raw validator lives only in the cookie.
     *
     * @param  int  $userId
     * @return void
     */
    public function rememberMe($userId): void
    {
        if (empty($this->config['rememberMe']['enabled'])) {
            return;
        }

        $selector = Str::random(24);
        $validator = Str::random(48);
        $expires = Carbon::now()->addDays((int) ($this->config['rememberMe']['expire_days'] ?? 30));

        $data = [
            'user_id' => $userId,
            'selector' => $selector,
            'hashedvalidator' => hash('sha256', $validator),
            'token_type' => 'remember_me',
            'expires_at' => $expires,
        ];

        // A user keeps a single remember-me token; rotate it on each issue.
        $existing = AuthModel::getAuthTokenByUserId($userId);

        if (empty($existing)) {
            AuthModel::insertToken($data);
        } else {
            AuthModel::updateToken($data);
        }

        Cookie::queue(Cookie::make(
            'remember',
            $selector.':'.$validator,
            max((int) $expires->diffInMinutes(), 1),
            '/',
            config('session.domain'),
            (bool) config('session.secure', false),
            true,   // HttpOnly
            false,  // raw
            config('session.same_site', 'lax') ?? 'lax'
        ));
    }

    /**
     * Rotate the remember-me token after a successful cookie login.
     *
     * @param  int  $userId
     * @return void
     */
    public function rememberMeReset($userId): void
    {
        $this->rememberMe($userId);
    }

    /**
     * --------------------------------------------------------------------------
     * CHECK REMEMBER ME COOKIE
     * --------------------------------------------------------------------------
     *
     * Validates a remember-me cookie against the auth_tokens table, honoring
     * expiry, and re-establishes the session on success.
     *
     * @return bool  True when a session was restored from the cookie.
     */
    public function checkCookie(): bool
    {
        if (session()->has('isLoggedIn') || Auth::check()) {
            return true;
        }

        if (session()->get('lockscreen') == true) {
            return false;
        }

        $remember = Cookie::get('remember');

        if (empty($remember) || ! is_string($remember) || ! str_contains($remember, ':')) {
            return false;
        }

        [$selector, $validator] = explode(':', $remember, 2);

        if ($selector === '' || $validator === '') {
            return false;
        }

        $token = AuthToken::where('selector', $selector)->first();

        if (! $token || ! hash_equals((string) $token->hashedvalidator, hash('sha256', $validator))) {
            return false;
        }

        // Reject (and purge) expired tokens.
        if ($token->expires_at && Carbon::parse($token->expires_at)->isPast()) {
            AuthModel::deleteTokenByUserId($token->user_id);
            Cookie::queue(Cookie::forget('remember'));
            return false;
        }

        $user = User::find($token->user_id);

        if (! $user || (int) $user->activated !== 1) {
            return false;
        }

        // Probabilistic forced re-authentication (0 = disabled).
        $forceLogin = (int) config('auth.force_login', 0);
        if ($forceLogin > 0 && random_int(1, 100) <= $forceLogin) {
            AuthModel::deleteTokenByUserId($token->user_id);
            Cookie::queue(Cookie::forget('remember'));
            return false;
        }

        $this->setUserSession($user);

        if (! empty($this->config['rememberMe']['renew'])) {
            $this->rememberMeReset($user->id);
        }

        return true;
    }

    /**
     * Queue a welcome e-mail for the given address.
     *
     * @param  string  $email
     * @param  string  $message
     * @return void
     */
    public function sendWelcomeEmail(string $email, string $message = 'Welcome to our application!'): void
    {
        SendWelcomeEmail::dispatch($email, $message);
    }

    /**
     * --------------------------------------------------------------------------
     * LOGOUT
     * --------------------------------------------------------------------------
     *
     * Revokes the remember-me token, clears the remember cookie, logs out of
     * Laravel's guard and destroys the session.
     *
     * @return void
     */
    public function logout(): void
    {
        try {
            if (session()->has('id')) {
                AuthModel::deleteTokenByUserId(session()->get('id'));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to revoke remember-me token on logout: '.$e->getMessage());
        }

        Cookie::queue(Cookie::forget('remember'));

        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * Resolve the dashboard path for the current session role.
     *
     * @return string
     */
    public function autoRedirect(): string
    {
        $redirects = $this->config['assign_redirect'] ?? [];
        $role = strtolower((string) session()->get('role', ''));

        if ($role !== '' && isset($redirects[$role])) {
            return $redirects[$role];
        }

        return '/admin';
    }

    /**
     * Encode a numeric id for use inside URL path segments.
     * URL-safe base64 without padding.
     *
     * @param  mixed  $id
     * @return string
     */
    public static function encodeId($id): string
    {
        return rtrim(strtr(base64_encode((string) $id), '+/', '-_'), '=');
    }

    /**
     * Decode an id produced by encodeId(). Returns null when invalid.
     * Also accepts legacy standard-base64 ids for backwards compatibility.
     *
     * @param  mixed  $value
     * @return int|null
     */
    public static function decodeId($value): ?int
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        // Plain numeric ids are accepted as-is (e.g. resend-activation/{id}).
        if (ctype_digit($value)) {
            return (int) $value;
        }

        $normalized = strtr($value, '-_', '+/');
        $padded = $normalized.str_repeat('=', (4 - strlen($normalized) % 4) % 4);
        $decoded = base64_decode($padded, true);

        if ($decoded === false || ! ctype_digit($decoded)) {
            return null;
        }

        return (int) $decoded;
    }
}
