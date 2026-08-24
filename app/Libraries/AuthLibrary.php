<?php

/**
 * --------------------------------------------------------------------
 * LARAVEL - AuthLibrary
 * --------------------------------------------------------------------
 *
 * This content is released under the MIT License (MIT)
 *
 * @package    AuthLibrary
 * @author     Your Name
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       Your link or documentation
 * @since      Version 1.0
 */

namespace App\Libraries;

use App\Models\AuthModel;
use App\Models\AuthToken;
use App\Models\User;
use Config\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
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
    protected $AuthModel;
    protected $config;
    protected $session;
    protected $request;
    protected $sendEmail;

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
     * Helper to get user's primary Spatie role details safely.
     */
    private function getUserRoleDetails($userId)
    {
        $roleRecord = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $userId)
            ->where('model_has_roles.model_type', User::class)
            ->select('roles.id', 'roles.name')
            ->first();

        return [
            'id' => $roleRecord ? $roleRecord->id : null,
            'name' => $roleRecord ? $roleRecord->name : null,
        ];
    }

    /**
     * Generate Token
     */
    public function generateToken($user, $tokentype)
    {
        $token = Str::random(40);
        $encodedToken = base64_encode($token);
        $hashedToken = Hash::make($token);

        if ($tokentype === 'reset_token') {
            $tokenexpire = 'reset_expire';
            $expireTime = config('auth.reset_token_expire') ?? 1;
        } elseif ($tokentype === 'activate_token') {
            $tokenexpire = 'activate_expire';
            $expireTime = config('auth.activate_token_expire') ?? 24;
        } else {
            throw new InvalidArgumentException('Invalid token type provided.');
        }

        $TokenExpireTime = Carbon::now()->addHours($expireTime);

        $user->update([
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            $tokentype => $hashedToken,
            $tokenexpire => $TokenExpireTime,
        ]);

        return $encodedToken;
    }

    /**
     * LOGIN USER
     */
    public function LoginUser($email, $rememberMe)
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

        $userID = $user->id;

        $rememberConfig = config('auth.rememberMe');
        if (!empty($rememberConfig['enabled']) && $rememberMe == '1') {
            $this->rememberMe($userID);
            session(['rememberme' => $rememberMe]);
        }

        session(['lockscreen' => false]);

        $this->setUserSession($user);

        return redirect()->to($this->autoredirect())->with('success', 'Login successful!');
    }

    /**
     * REGISTER USER
     */
    public function registerUser(array $userData)
    {
        $defaultRole = config('auth.default_role', 'admin');
        $userData['roles'] = $defaultRole;

        AuthModel::create($userData);

        $user = $this->AuthModel->where('email', $userData['email'])->first();

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

    public function sendActivationEmail($user, $activationToken)
    {
        $base64decodedId = base64_encode($user->id);
        $activationLink = url('/activate/' . $base64decodedId . '/' . $activationToken);

        $emailData = [
            'to' => $user->email,
            'subject' => config('mail.activation_email_subject', 'Activate Your Account'),
        ];

        try {
            Mail::to($emailData['to'])->send(new SendActivationMail($user, $activationLink));
            session()->flash('success', __('auth.account_created'));
            return true;
        } catch (\Exception $e) {
            session()->flash('danger', __('auth.error_occurred'));
            return false;
        }
    }

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

    public function activateUser($id, $token)
    {
        $decodedId = base64_decode($id);
        $decodedToken = base64_decode($token);

        $user = AuthModel::findOrFail($decodedId);

        if (!$user->activate_token) {
            Session::flash('danger', __('No activation token found.'));
            return redirect()->to('/');
        }

        $resetExpiry = $user->activate_expire;
        if (Carbon::now()->greaterThanOrEqualTo(Carbon::parse($resetExpiry))) {
            Session::flash('danger', __('The activation link has expired.'));
            return false;
        }

        if (!Hash::check($decodedToken, $user->activate_token)) {
            Session::flash('danger', Lang::get('auth.invalidToken'));
            return false;
        }

        $user->update([
            'activated' => true,
            'activate_token' => null,
            'activate_expire' => null,
        ]);

        Session::flash('success', Lang::get('auth.account_activated'));
        return true;
    }

    public function Forgotpassword($email)
    {
        $user = AuthModel::where('email', $email)->first();
        $encodedtoken = $this->generateToken($user, 'reset_token');
        $this->ResetEmail($user, $encodedtoken);
        return;
    }

    public function ResetEmail($user, $encodedToken)
    {
        $base64decodedId = base64_encode($user->id);
        $resetLink = url('/resetpassword/' . $base64decodedId . '/' . $encodedToken);

        $emailData = [
            'to' => $user->email,
            'subject' => config('mail.reset_email_subject', 'Password Reset Request'),
        ];

        try {
            Mail::to($emailData['to'])->send(new ResetPasswordMail($user, $resetLink));
            session()->flash('success', __('auth.resetSent'));
            return true;
        } catch (\Exception $e) {
            session()->flash('danger', __('auth.errorOccured'));
            return false;
        }
    }

    public function ResetPassword($id, $token)
    {
        $decodedToken = base64_decode($token);
        $decodedId = base64_decode($id);

        $user = AuthModel::find($decodedId);

        if (!$user) {
            Session::flash('danger', Lang::get('auth.userNotFound'));
            return false;
        }

        $resetExpiry = $user->reset_expire;
        $timeNow = Carbon::now();

        if (!$resetExpiry || $timeNow->greaterThanOrEqualTo(Carbon::parse($resetExpiry))) {
            Session::flash('danger', Lang::get('auth.linkExpired'));
            return false;
        }

        if (!$user->reset_token || !Hash::check($decodedToken, $user->reset_token)) {
            Session::flash('danger', Lang::get('auth.noAuth'));
            return false;
        } else {
            Session::flash('success', Lang::get('auth.passwordAuthorised'));
            return $decodedId;
        }
    }

    /**
     * SET USER SESSION
     */
    public function setUserSession($user)
    {
        $roleDetails = $this->getUserRoleDetails($user->id);
        $roleName = $roleDetails['name'] ?? (is_string($user->roles) ? $user->roles : 'user');

        $data = [
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'role'           => $roleName,
            'user_role_id'   => $roleDetails['id'],
            'user_role_name' => $roleName,
            'isLoggedIn'     => true,
            'ipaddress'      => request()->ip(),
        ];

        session($data);
        $this->loginlog();

        return true;
    }

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

    public function loginlogFail(string $email)
    {
        $user = AuthModel::where('email', $email)->first();

        if ($user) {
            $roleDetails = $this->getUserRoleDetails($user->id);
            $roleName = $roleDetails['name'] ?? (is_string($user->roles) ? $user->roles : 'user');

            $logData = [
                'user_id'        => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'role'           => $roleName,
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
     * REMEMBER ME
     */
    public function rememberMe($userID)
    {
        if (empty($this->config['rememberMe']['enabled'])) {
            return;
        }

        $selector = Str::random(12);
        $validator = Str::random(20);
        $expireDays = $this->config['rememberMe']['expire_days'] ?? 30;
        $expireMinutes = $expireDays * 24 * 60;
        $expires = Carbon::now()->addDays($expireDays);

        $hashedValidator = hash('sha256', $validator);
        $token = $selector . ':' . $validator;

        $data = [
            'user_id'         => $userID,
            'selector'        => $selector,
            'hashedvalidator' => $hashedValidator,
            'expires'         => $expires,
        ];

        // Safe query handling using AuthToken model directly to prevent missing method errors
        AuthToken::updateOrCreate(
            ['user_id' => $userID],
            $data
        );

        Cookie::queue(
            'remember',
            $token,
            $expireMinutes,
            '/',
            config('session.domain', null),
            config('session.secure', false),
            true
        );
    }

    public function rememberMeReset($userID)
    {
        $this->rememberMe($userID);
    }

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
            return false;
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

        $this->setUserSession($user);
        $this->rememberMeReset($user->id);

        return;
    }

    public function sendWelcomeEmail($email)
    {
        if ($this->sendEmail) {
            $this->sendEmail->send($email, 'Welcome to our application', 'welcome-email-template');
        }
    }

    public function logout()
    {
        $userId = $this->session->get('id');
        if ($userId) {
            AuthToken::where('user_id', $userId)->delete();
        }
        Session::flush();
        return;
    }

    public function autoredirect()
    {
        $redirect = $this->config['assign_redirect'] ?? [];
        $role = strtolower((string)$this->session->get('role'));

        if (isset($redirect[$role])) {
            return $redirect[$role];
        }

        return '/admin';
    }
}
