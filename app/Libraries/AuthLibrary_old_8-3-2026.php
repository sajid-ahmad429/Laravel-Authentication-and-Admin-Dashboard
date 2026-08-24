<?php

/**
 * --------------------------------------------------------------------
 * LARAVEL - AuthLibrary
 * --------------------------------------------------------------------
 *
 * This content is released under the MIT License (MIT)
 *
 * @package     AuthLibrary
 * @author      Your Name
 * @license     https://opensource.org/licenses/MIT MIT License
 * @link        Your link or documentation
 * @since       Version 1.0
 */

namespace App\Libraries;

use App\Models\AuthModel;
use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use App\Mail\SendActivationMail;
use InvalidArgumentException;
use Exception;

/**
 * AuthLibrary - Custom Authentication Library
 */
class AuthLibrary
{
    protected AuthModel $AuthModel;
    protected $config;
    protected $session;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->AuthModel = new AuthModel();
        $this->config = config('auth');
        $this->session = session();
    }

    /**
     * Generate Token
     */
    public function generateToken($user, string $tokentype): string
    {
        if (is_numeric($user)) {
            $user = User::find($user);
        }

        if (!$user) {
            throw new InvalidArgumentException('Invalid user provided for token generation.');
        }

        $token = Str::random(40);
        $encodedToken = base64_encode($token);
        $hashedToken = Hash::make($token);

        if ($tokentype === 'reset_token') {
            $tokenexpire = 'reset_expire';
            $expireTime = config('auth.reset_token_expire', 1);
        } elseif ($tokentype === 'activate_token') {
            $tokenexpire = 'activate_expire';
            $expireTime = config('auth.activate_token_expire', 24);
        } else {
            throw new InvalidArgumentException('Invalid token type provided.');
        }

        $TokenExpireTime = Carbon::now()->addHours($expireTime);

        $user->update([
            $tokentype   => $hashedToken,
            $tokenexpire => $TokenExpireTime,
        ]);

        return $encodedToken;
    }

    /**
     * Login User
     */
    public function LoginUser(string $email, $rememberMe)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            session()->flash('danger', __('User not found.'));
            return redirect()->back();
        }

        if ($user->activated == 0) {
            session()->flash('danger', __('Your account is not activated.'));
            session()->flash('resetlink', '<a href="' . route('resend.activation', $user->id) . '">Resend Activation Email</a>');
            return redirect()->back();
        }

        $rememberConfig = config('auth.rememberMe');
        if (!empty($rememberConfig['enabled']) && $rememberMe == '1') {
            $this->rememberMe($user->id);
            session(['rememberme' => $rememberMe]);
        }

        session(['lockscreen' => false]);
        $this->setUserSession($user);

        return redirect()->intended('dashboard')->with('success', 'Login successful!');
    }

    /**
     * Register User
     */
    public function registerUser(array $userData): bool
    {
        $defaultRole = config('auth.default_role', 'admin');
        $userData['roles'] = $defaultRole;

        AuthModel::create($userData);

        $user = AuthModel::where('email', $userData['email'])->first();

        if (!$user) {
            session()->flash('danger', __('auth.error_occurred'));
            return false;
        }

        $sendActivationEmail = config('auth.send_activation_email', true);

        if ($sendActivationEmail) {
            $token = $this->generateToken($user, 'activate_token');
            $result = $this->sendActivationEmail($user, $token);

            if ($result) {
                session()->flash('success', __('auth.account_created'));
                return true;
            } else {
                session()->flash('danger', __('auth.error_occurred'));
                return false;
            }
        }

        $user->update(['activated' => true]);

        session()->flash('success', __('auth.account_created_no_auth'));
        return true;
    }

    /**
     * Send Activation Email
     */
    public function sendActivationEmail($user, string $activationToken): bool
    {
        if (is_numeric($user)) {
            $user = User::find($user);
        }

        if (!$user) {
            return false;
        }

        $base64encodedId = base64_encode($user->id);
        $activationLink = url('/activate/' . $base64encodedId . '/' . $activationToken);

        try {
            Mail::to($user->email)->send(new SendActivationMail($user, $activationLink));
            session()->flash('success', __('auth.account_created'));
            return true;
        } catch (Exception $e) {
            session()->flash('danger', __('auth.error_occurred'));
            return false;
        }
    }

    /**
     * Resend Activation Email
     */
    public function resendActivation($id)
    {
        $user = AuthModel::where('id', $id)->first();

        if (!$user) {
            return redirect()->back()->with('error', __('User not found.'));
        }

        $encodedtoken = $this->generateToken($user, 'activate_token');
        $result = $this->sendActivationEmail($user, $encodedtoken);

        if ($result) {
            session()->flash('success', __('Activation email re-sent successfully.'));
            return true;
        } else {
            session()->flash('error', __('An error occurred while sending the email.'));
            return false;
        }
    }

    /**
     * Activate User
     */
    public function activateUser($id, string $token)
    {
        $decodedId = base64_decode($id);
        $decodedToken = base64_decode($token);

        $user = AuthModel::find($decodedId);

        if (!$user || !$user->activate_token) {
            Session::flash('danger', __('No activation token found.'));
            return redirect()->to('/');
        }

        if (Carbon::now()->greaterThanOrEqualTo(Carbon::parse($user->activate_expire))) {
            Session::flash('danger', __('The activation link has expired.'));
            return false;
        }

        if (!Hash::check($decodedToken, $user->activate_token)) {
            Session::flash('danger', Lang::get('auth.invalidToken'));
            return false;
        }

        $user->update([
            'activated'       => true,
            'activate_token'  => null,
            'activate_expire' => null,
        ]);

        Session::flash('success', Lang::get('auth.account_activated'));
        return true;
    }

    /**
     * Forgot Password
     */
    public function Forgotpassword(string $email)
    {
        $user = AuthModel::where('email', $email)->first();
        
        if (!$user) {
            return false;
        }

        $encodedtoken = $this->generateToken($user, 'reset_token');
        $this->ResetEmail($user, $encodedtoken);

        return true;
    }

    /**
     * Reset Email
     */
    public function ResetEmail($user, string $encodedToken): bool
    {
        if (is_numeric($user)) {
            $user = User::find($user);
        }

        if (!$user) {
            return false;
        }

        $base64decodedId = base64_encode($user->id);
        $resetLink = url('/resetpassword/' . $base64decodedId . '/' . $encodedToken);

        try {
            Mail::to($user->email)->send(new ResetPasswordMail($user, $resetLink));
            session()->flash('success', __('auth.resetSent'));
            return true;
        } catch (Exception $e) {
            session()->flash('danger', __('auth.errorOccured'));
            return false;
        }
    }

    /**
     * Reset Password
     */
    public function ResetPassword($id, string $token)
    {
        $decodedToken = base64_decode($token);
        $decodedId = base64_decode($id);

        $user = AuthModel::find($decodedId);

        if (!$user) {
            Session::flash('danger', Lang::get('auth.userNotFound'));
            return false;
        }

        if (Carbon::now()->greaterThanOrEqualTo(Carbon::parse($user->reset_expire))) {
            Session::flash('danger', Lang::get('auth.linkExpired'));
            return false;
        }

        // Fixed array syntax bug to object property syntax
        if (!Hash::check($decodedToken, $user->reset_token)) {
            Session::flash('danger', Lang::get('auth.noAuth'));
            return false;
        }

        Session::flash('success', Lang::get('auth.passwordAuthorised'));
        return $decodedId;
    }

    /**
     * Set User Session
     */
    public function setUserSession($user): bool
    {
        $data = [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->roles,
            'isLoggedIn' => true,
            'ipaddress'  => request()->ip(),
        ];

        session($data);
        $this->loginlog();

        return true;
    }

    /**
     * Log Login
     */
    public function loginlog()
    {
        if ($this->session->has('isLoggedIn')) {
            $logdata = [
                'user_id'        => $this->session->get('id'),
                'name'           => $this->session->get('name'),
                'email'          => $this->session->get('email'),
                'role'           => $this->session->get('role'),
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
                'device_type'    => 'Desktop',
                'successful'     => true,
                'failure_reason' => null,
                'logged_in_at'   => Carbon::now(),
            ];

            $this->AuthModel->logLogin($logdata);
        }
    }

    /**
     * Log Login Failure
     */
    public function loginlogFail(string $email)
    {
        $user = AuthModel::where('email', $email)->first();
        if ($user) {
            $logData = [
                'user_id'        => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'role'           => $user->roles,
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
                'device_type'    => 'Desktop',
                'successful'     => false,
                'failure_reason' => 'Invalid credentials',
                'logged_in_at'   => Carbon::now(),
            ];

            $this->AuthModel->logLogin($logData);
        }
    }

    /**
     * Remember Me
     */
    public function rememberMe($userID)
    {
        if (empty($this->config['rememberMe']['enabled'])) {
            return;
        }

        $selector = Str::random(12);
        $validator = Str::random(20);
        $expires = Carbon::now()->addDays($this->config['rememberMe']['expire_days']);

        $hashedValidator = hash('sha256', $validator);
        $token = $selector . ':' . $validator;

        $data = [
            'user_id'         => $userID,
            'selector'        => $selector,
            'hashedvalidator' => $hashedValidator,
            'expires_at'      => $expires,
        ];

        $result = $this->AuthModel->GetAuthTokenByUserId($userID);
        if (empty($result)) {
            $this->AuthModel->insertToken($data);
        } else {
            $this->AuthModel->updateToken($data);
        }

        Cookie::queue(
            'remember',
            $token,
            $expires->diffInMinutes(),
            '/',
            config('session.domain', null),
            config('session.secure', false),
            true
        );
    }

    /**
     * Check Remember Me Cookie
     */
    public function checkCookie()
    {
        if (Session::get('lockscreen') == true) {
            return;
        }

        $remember = Cookie::get('remember');
        if (empty($remember)) {
            return;
        }

        $parts = explode(':', $remember);
        if (count($parts) !== 2) {
            return;
        }
        list($selector, $validator) = $parts;
        $validator = hash('sha256', $validator);

        $token = AuthToken::where('selector', $selector)->first();
        if (empty($token)) {
            return false;
        }

        if (!hash_equals($token->hashedvalidator, $validator)) {
            return false;
        }

        $user = User::find($token->user_id);
        if (empty($user)) {
            return false;
        }

        if (!empty(config('auth.forceLogin')) && config('auth.forceLogin') > 1) {
            if (rand(1, 100) < config('auth.forceLogin')) {
                $this->AuthModel->deleteTokenByUserId($token->user_id);
                return;
            }
        }

        $this->setUserSession($user);
        return;
    }

    /**
     * Logout
     */
    public function logout()
    {
        if ($this->session->has('id')) {
            $this->AuthModel->DeleteTokenByUserId($this->session->get('id'));
        }
        Session::flush();
        return;
    }

    /**
     * Auto Redirect based on Role
     */
    public function autoredirect()
    {
        $redirect = $this->config['assign_redirect'] ?? [];
        $role = $this->session->get('role');

        if (isset($redirect[$role])) {
            return $redirect[$role];
        }

        return '/default-page';
    }
}