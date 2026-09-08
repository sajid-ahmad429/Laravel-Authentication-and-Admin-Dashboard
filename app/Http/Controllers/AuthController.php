<?php

namespace App\Http\Controllers;

use App\Libraries\AuthLibrary;
use App\Models\User;
use App\Rules\ValidPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    protected AuthLibrary $authLibrary;

    public function __construct(AuthLibrary $authLibrary)
    {
        $this->authLibrary = $authLibrary;
    }

    /**
     * Landing page → login.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('login');
    }

    /**
     * Login form (guests only; authenticated users go to their panel).
     */
    public function showLogin()
    {
        // Auto-login via remember-me cookie when applicable.
        if (! Session::has('isLoggedIn')) {
            $this->authLibrary->checkCookie();
        }

        if (Session::has('isLoggedIn')) {
            return redirect()->to($this->authLibrary->autoRedirect());
        }

        return view('admin.auth.login');
    }

    /**
     * Handle a login attempt (route applies named rate limiting).
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'email.required'    => 'Please enter your email address.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Please enter your password.',
        ]);

        $email     = strtolower(trim((string) $request->input('email')));
        $password  = (string) $request->input('password');
        $remember  = $request->boolean('rememberme');

        $user = User::where('email', $email)->first();

        // Uniform failure response — never reveal whether the email exists.
        $genericFailure = __('auth.failed');

        if (! $user || ! Hash::check($password, $user->password)) {
            $this->authLibrary->logLoginFailure($user, $email);

            return back()
                ->withInput($request->only('email'))
                ->with('danger', $genericFailure);
        }

        if ((int) $user->activated !== 1) {
            $this->authLibrary->logLoginFailure($user, $email, 'Account not activated');

            return back()
                ->withInput($request->only('email'))
                ->with('danger', 'Your account is not activated yet.')
                ->with('showResend', true)
                ->with('resendEmail', $email);
        }

        if ((int) $user->status !== 1 || (int) $user->trash !== 0) {
            $this->authLibrary->logLoginFailure($user, $email, 'Account suspended');

            return back()->withInput($request->only('email'))
                ->with('danger', 'This account has been suspended. Please contact support.');
        }

        $this->authLibrary->login($user, $remember);

        $firstName = trim(explode(' ', (string) $user->name)[0] ?? 'there');

        return redirect()
            ->intended($this->authLibrary->autoRedirect())
            ->with('success', 'Welcome back, '.$firstName.'!');
    }

    /**
     * Registration form.
     */
    public function showRegister(): View
    {
        return view('admin.auth.register');
    }

    /**
     * Handle registration.
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[\pL\pN\s.\-]+$/u'],
            'email'    => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', ValidPassword::rules()],
        ], [
            'username.required' => 'Please enter your full name.',
            'email.unique'      => 'An account with this email already exists.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $user = $this->authLibrary->registerUser([
            'name'     => trim($request->input('username')),
            'email'    => strtolower(trim($request->input('email'))),
            'password' => $request->input('password'),
        ]);

        if (! $user) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->with('danger', 'Registration failed. Please try again in a moment.');
        }

        $message = config('auth.send_activation_email', true)
            ? 'Account created! We sent an activation link to your email address.'
            : 'Account created successfully. You can now sign in.';

        return redirect()->route('login')->with('success', $message);
    }

    /**
     * Re-send an activation email (by email address — no ID enumeration).
     */
    public function resendActivation(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);

        try {
            $this->authLibrary->resendActivation(strtolower(trim($request->input('email'))));
        } catch (Throwable $e) {
            Log::error('Resend activation failed: '.$e->getMessage());
        }

        // Identical response regardless of account existence (no enumeration).
        return redirect()->route('login')
            ->with('success', 'If that email belongs to an unactivated account, a fresh activation link is on its way.');
    }

    /**
     * Consume activation token from email link.
     */
    public function activateUser(string $id, string $token): RedirectResponse
    {
        $result = $this->authLibrary->activateUser($id, $token);

        return redirect()->route('login')->with(
            $result['ok'] ? 'success' : 'danger',
            $result['message']
        );
    }

    /**
     * Forgot password form.
     */
    public function showForgotPassword(): View
    {
        return view('admin.auth.forgotpassword');
    }

    /**
     * Handle forgot password request. The response never reveals whether the
     * email is registered (anti-enumeration).
     */
    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);

        try {
            $this->authLibrary->sendResetLink(strtolower(trim($request->input('email'))));
        } catch (Throwable $e) {
            Log::error('Forgot password dispatch failed: '.$e->getMessage());
        }

        return redirect()->route('login')
            ->with('success', 'If that email is registered, a password reset link has been sent.');
    }

    /**
     * Reset link landing: validate token, then grant a short-lived,
     * session-bound authorization to set a new password.
     */
    public function resetPassword(string $id, string $token): RedirectResponse
    {
        $userId = $this->authLibrary->validateResetToken($id, $token);

        if (! $userId) {
            return redirect()->route('forgotpassword')
                ->with('danger', 'This password reset link is invalid or has expired. Please request a new one.');
        }

        $this->authLibrary->grantPasswordReset($userId);

        return redirect()->route('password.reset.form');
    }

    /**
     * New password form (only reachable right after a valid token check).
     */
    public function showResetPasswordForm(Request $request)
    {
        if (! session('password_reset_grant')) {
            return redirect()->route('forgotpassword')
                ->with('danger', 'Your reset session has expired. Please request a new link.');
        }

        return view('admin.auth.resetpassword');
    }

    /**
     * Store the new password. SECURITY: the request is only honored when the
     * session carries a valid reset grant (issued after the email token was
     * verified). The old implementation accepted a user id directly, allowing
     * anyone to take over any account.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $grant = session('password_reset_grant');
        $userId = is_array($grant) ? (int) ($grant['user_id'] ?? 0) : 0;

        if (! $userId || ! $this->authLibrary->consumePasswordResetGrant($userId)) {
            return redirect()->route('forgotpassword')
                ->with('danger', 'Your reset session has expired. Please request a new link.');
        }

        $request->validate([
            'password' => ['required', 'string', 'confirmed', ValidPassword::rules()],
        ], [
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        if (! $this->authLibrary->applyNewPassword($userId, $request->input('password'))) {
            return redirect()->route('forgotpassword')->with('danger', 'Account not found.');
        }

        return redirect()->route('login')->with('success', 'Password updated successfully. Please sign in.');
    }

    /**
     * Authenticated dashboard — role aware, real aggregates only.
     */
    public function dashboard(Request $request)
    {
        $stats = [
            'total'     => User::notDeleted()->count(),
            'active'    => User::notDeleted()->where('status', 1)->count(),
            'new_today' => User::notDeleted()->whereDate('created_at', today())->count(),
            'this_month'=> User::notDeleted()->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        $recentUsers = User::notDeleted()
            ->select(['id', 'name', 'email', 'roles', 'plan', 'status', 'activated', 'created_at'])
            ->latest('id')
            ->limit(6)
            ->get();

        return view('masters.home', [
            'activeMenu'  => 'dashboard',
            'stats'       => $stats,
            'recentUsers' => $recentUsers,
        ]);
    }

    /**
     * Log the user out (POST only — CSRF protected).
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->authLibrary->logout();

        return redirect()->route('login')->with('success', 'You have been signed out.');
    }
}
