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

use App\Models\AuthModel;  // Assuming you have an AuthModel
use App\Models\AuthToken;
use App\Models\User;
use Config\Auth;
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



/**
 * AuthLibrary - Custom Authentication Library
 */
class AuthLibrary
{
    protected $AuthModel;
    protected $config;
    protected $Session;
    protected $request;

    /**
     * Constructor
     *
     * Initialize the necessary services and models.
     */
    public function __construct()
    {
        // Initialize the models and services
        $this->AuthModel = new AuthModel();
        $this->config = config('auth');  // You can access config files this way in Laravel
        $this->session = session();
    }


    /*
     * --------------------------------------------------------------------------
     * Generate Token
     * --------------------------------------------------------------------------
     *
     * Generates a random token encodes it then hashes it.
     * Sets the expiry time for the token
     *
     * @param  int $user
     * @param  int $tokentype
     * @return int $encodedtoken
     *
    */

    public function generateToken($user, $tokentype)
    {
        // Generate a random token
        $token = Str::random(40);

        // Encode the token
        $encodedToken = base64_encode($token);

        // Hash the encoded token
        $hashedToken = Hash::make($token);

        // Determine token expiry time based on token type
        if ($tokentype === 'reset_token') {
            $tokenexpire = 'reset_expire';
            $expireTime = config('auth.reset_token_expire') ?? 1; // Default to 60 minutes
        } elseif ($tokentype === 'activate_token') {
            $tokenexpire = 'activate_expire';
            $expireTime = config('auth.activate_token_expire') ?? 24; // Default to 24 hours
        } else {
            throw new InvalidArgumentException('Invalid token type provided.');
        }

        // Set the expiry time
        $TokenExpireTime = Carbon::now()->addHours($expireTime);

        // UPDATE DB WITH HASHED TOKEN
        // Update the user's record in the database
        $user->update([
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            $tokentype => $hashedToken,
            $tokenexpire => $TokenExpireTime,
        ]);

        // Return the encoded token
        return $encodedToken;
    }

    /**
     * --------------------------------------------------------------------------
     * LOGIN USER
     * --------------------------------------------------------------------------
     *
     * Form validation done in controller
     * Gets the user from DB
     * Checks if their account is activated
     * Sets the user session and logs them in
     *
     * @param  string $email
     * @return true
    */

    public function LoginUser($email, $rememberMe)
    {
        // GET USER DETAILS FROM DB
        $user = User::where('email', $email)->first();

        // Check if the user exists
        if (!$user) {
            session()->flash('danger', __('User not found.'));
            return redirect()->back();
        }

        // Check if the account is activated
        if ($user->activated == 0) {
            // Account not activated, set a link to resend activation email
            session()->flash('danger', __('Your account is not activated.'));
            session()->flash('resetlink', '<a href="' . route('resend.activation', $user->id) . '">Resend Activation Email</a>');
            return redirect()->back();
        }

        // SET USER ID AS A VARIABLE
        $userID = $user->id;

        // IF REMEMBER ME FUNCTION IS SET TO TRUE IN CONFIG
        $rememberConfig = config('auth.rememberMe'); // Access Remember Me configuration
        if ($rememberConfig['enabled'] && $rememberMe == '1') {
            $this->rememberMe($userID);
            session(['rememberme' => $rememberMe]); // Save in session
        }

        session(['lockscreen' => false]); // Set lockscreen to false

        // SET USER SESSION
        $this->setUserSession($user);

        return redirect()->intended('dashboard')->with('success', 'Login successful!');
    }

       /**
     * --------------------------------------------------------------------------
     * REGISTER USER
     * --------------------------------------------------------------------------
     *
     * Form validation done in controller
     * Save user details to DB
     * Send activation email if config is set to true
     * If config is false manually activate account
     *
     * @param  array $userData
     * @return true
    */

    public function registerUser(array $userData)
    {
        // Add User to Default Role
        $defaultRole = config('auth.default_role', 'admin'); // Retrieve default role from config
        $userData['roles'] = $defaultRole;

        // Save User Details to Database
        AuthModel::create($userData);

        // FIND OUR NEW USER BY EMAIL SO WE CAN GRAB NEW DETAILS
        $user = $this->AuthModel->where('email', $userData['email'])->first();

        // Check if the user was successfully created
        if (!$user) {
            session()->flash('danger', __('auth.error_occurred'));
            return false;
        }

        // Should We Send an Activation Email?
        $sendActivationEmail = config('auth.send_activation_email', true); // Retrieve email setting from config

        if ($sendActivationEmail) {
            // Generate a New Token
            $token = $this->generateToken($user, 'activate_token');

            // Generate and Send Activation Email
            $result = $this->sendActivationEmail($user, $token);

            if ($result) {
                session()->flash('success', __('auth.account_created'));
                return true;
            } else {
                session()->flash('danger', __('auth.error_occurred'));
                return false;
            }
        }

        // If Not Sending Activation Email, Activate the User Immediately
        $user->update(['activated' => true]);

        session()->flash('success', __('auth.account_created_no_auth'));
        return true;
    }

       /**
     * --------------------------------------------------------------------------
     * ACTIVATE EMAIL
     * --------------------------------------------------------------------------
     *
     * Set up the activation email if config is set to true
     * Send Email
     *
     * @param  int $user
     * @param  int $encodedtoken
     * @return boolean
    */

    public function sendActivationEmail($user, $activationToken)
    {
        $base64decodedId = base64_encode($user->id);
        // Activation link to include in the email template
        $activationLink = url('/activate/' . $base64decodedId . '/' . $activationToken);

        // Data to pass to the email template
        $data = [
            'userid' => $user->id,
            'name' => $user->name,
            'activationLink' => $activationLink,
        ];

        // SET EMAIL DATA
        $emailData = [
            'to' => $user->email,
            'subject' => config('mail.activation_email_subject', 'Activate Your Account'),
        ];

        try {
            // SEND EMAIL USING A MAILABLE CLASS
            Mail::to($emailData['to'])->send(new SendActivationMail($user, $activationLink));

            // SUCCESS MESSAGE
            session()->flash('success', __('auth.account_created'));
            return true;

        } catch (\Exception $e) {
            // ERROR MESSAGE
            session()->flash('danger', __('auth.error_occurred'));
            return false;
        }
    }

    /**
     * --------------------------------------------------------------------------
     * RESEND ACTIVATION EMAIL
     * --------------------------------------------------------------------------
     *
     * Resends the user activation email
     *
     * @param  int $id
     * @return boolean
    */

        public function resendActivation($id)
        {
            // Find user by ID
            $user = AuthModel::where('id', $id)->first();

            if (!$user) {
                return redirect()->back()->with('error', __('User not found.'));
            }

            // Generate a new activation token
            $encodedtoken = $this->generateToken($user, 'activate_token');
            $result = $this->sendActivationEmail($user, $encodedtoken);
            if ($result) {
                // Send success flash message and return true
                session()->flash('success', __('Activation email re-sent successfully.'));
                return true;
            } else {
                // Send error flash message and return false
                session()->flash('error', __('An error occurred while sending the email.'));
                return false;
            }
        }

     /**
     * --------------------------------------------------------------------------
     * ACTIVATE USER
     * --------------------------------------------------------------------------
     *
     * Incoming request from email link to activate the user
     * Decode the token and get user details from DB
     * Check if token is valid and hasnt expired
     * Update user to activated
     *
     * @param  int $id
     * @param  int $token
     * @return void
    */

    public function activateUser($id, $token)
    {
        // Decode the ID
        $decodedId = base64_decode($id);

        // Decode the token
        $decodedToken = base64_decode($token);

        // Find the user by ID
        $user = AuthModel::findOrFail($decodedId);

        // Check if the activation token exists
        if (!$user->activate_token) {
            // Set a flash message for the danger alert
            Session::flash('danger', __('No activation token found.'));
            // Redirect to the login page
            return redirect()->to('/');
        }

        // Check if the token has expired
        $resetExpiry = $user->activate_expire; // Assuming it's stored as a datetime
        if (Carbon::now()->greaterThanOrEqualTo(Carbon::parse($resetExpiry))) {
            Session::flash('danger', __('The activation link has expired.'));
            return false;
        }

        // Verify the token
        if (!Hash::check($decodedToken, $user->activate_token)) {
            Session::flash('danger', Lang::get('auth.invalidToken'));
            return false;
        }

        // Update user data
        $user->update([
            'activated' => true,
            'activate_token' => null, // Clear the token
            'activate_expire' => null, // Clear the expiry
        ]);

        // Set success message
        Session::flash('success', Lang::get('auth.account_activated'));
        return true;
    }


     /**
     * --------------------------------------------------------------------------
     * FORGOT PASSWORD
     * --------------------------------------------------------------------------
     *
     * @param  int $email
     * @return void
    */
    public function Forgotpassword($email) {

        // FIND USER BY EMAIL
        $user = AuthModel::where('email', $email)->first();
        // GENERATE A NEW TOKEN
        // SET THE TOKEN TYPE AS SECOND PARAMETER. Reset password token = 'reset_token'
        $encodedtoken = $this->generateToken($user, 'reset_token');
        // GENERATE AND SEND RESET EMAIL
        $data = $this->ResetEmail($user, $encodedtoken);

        return;
    }

     /**
     * --------------------------------------------------------------------------
     * RESET EMAIL
     * --------------------------------------------------------------------------
     *
     * Sends the user a password reset link email
     *
     * @param  array $user
     * @param  int $encodedtoken
     * @return boolean
    */

    public function ResetEmail($user, $encodedToken) {
        $base64decodedId = base64_encode($user->id);
        // RESET LINK TO INCLUDE IN EMAIL TEMPLATE
        $resetLink = url('/resetpassword/' . $base64decodedId . '/' . $encodedToken);

        // SET DATA TO PASS TO THE EMAIL VIEW
        $data = [
            'userid' => $user->id,
            'name' => $user->name,
            'resetlink' => $resetLink,
        ];

        // SET EMAIL DATA
        $emailData = [
            'to' => $user->email,
            'subject' => config('mail.reset_email_subject', 'Password Reset Request'),
        ];

        try {
            // SEND EMAIL USING A MAILABLE CLASS
            Mail::to($emailData['to'])->send(new ResetPasswordMail($user, $resetLink));

            // SUCCESS MESSAGE
            session()->flash('success', __('auth.resetSent'));
            return true;

        } catch (\Exception $e) {
            // ERROR MESSAGE
            session()->flash('danger', __('auth.errorOccured'));
            return false;
        }
    }

    /**
     * --------------------------------------------------------------------------
     * RESET PASSWORD
     * --------------------------------------------------------------------------
     *
     * Incoming request to reset password
     * Decode the token and get user details from DB
     * Check if token is valid and hasnt expired
     * Return user id to use on password reset form
     *
     * @param  int $id
     * @param  int $token
     * @return true $id
     */
    public function ResetPassword($id, $token) {
        // Decode the token
        $decodedToken = base64_decode($token);
        // Decode the id
        $decodedId = base64_decode($id);

        // Get user details from the database
        $user = AuthModel::find($decodedId);

        if (!$user) {
            // User not found, set flash message
            Session::flash('danger', Lang::get('auth.userNotFound'));
            return true; // Example redirect
        }

        // Fetch the expiry time for the token
        $resetExpiry = $user->reset_expire; // Assuming it's stored as a DateTime
        $timeNow = Carbon::now();

        // Check if the token has expired
        if ($timeNow->greaterThanOrEqualTo(Carbon::parse($resetExpiry))) {
            // Token has expired, set flash message
            Session::flash('danger', Lang::get('auth.linkExpired'));
            return true;// Example redirect
        }

        // Check the token against the hashed token in the database
        if (!password_verify($decodedToken, $user['reset_token'])) {
            // Token does not match, set flash message
            Session::flash('danger', Lang::get('auth.noAuth'));
            return true; // Example redirect
        } else {
            // Token is valid, set success message
            Session::flash('success', Lang::get('auth.passwordAuthorised'));
            return $decodedId;
        }
    }

    /**
     * --------------------------------------------------------------------------
     * SET USER SESSION
     * --------------------------------------------------------------------------
     *
     * Saves user details to session
     *
     * @param  \App\Models\User $user
     * @return bool
     */
    public function setUserSession($user)
    {
        // Prepare user session data
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles,
            'isLoggedIn' => true,
            'ipaddress' => request()->ip(), // Get IP address
        ];

        // Store session data
        session($data);

        // Log login details
        $this->loginlog();

        return true;
    }

     /**
     * --------------------------------------------------------------------------
     * lOG LOGIN
     * --------------------------------------------------------------------------
     *
     * Logs users login session to DB
     *
     * @return void
     */
    public function loginlog() {

        // LOG THE LOGIN IN DB
        if ($this->session->has('isLoggedIn')) {

            // BUILD DATA TO ADD TO auth_logins TABLE
            $logdata = [
                'user_id'    => $this->session->get('id'),
                'name'       => $this->session->get('name'),
                'role'       => $this->session->get('role'),
                'ip_address' => request()->ip(),
                'date'       => date('Y-m-d H:i:s'),
                'successful' => '1',
            ];

            // SAVE LOG DATA TO DB
            $this->AuthModel->LogLogin($logdata);
        }
    }




    /**
     * --------------------------------------------------------------------------
     * lOG LOGIN FAILURE
     * --------------------------------------------------------------------------
     *
     * If user login / verification failed log an unsuccesfull login attempt
     *
     * @param  mixed $email
     * @return void
    */

    public function loginlogFail($email)
    {
        // FIND USER BY EMAIL
        $user = AuthModel::where('email', $email)->first();
        if ($user) {
            // BUILD DATA TO ADD TO auth_logins TABLE
            $logData = [
                'user_id'    => $user->id,
                'name'  => $user->name,
                'role'       => $user->roles,
                'ip_address' => request()->ip(),  // Getting IP address in Laravel
                'date'       => Carbon::now(),  // Current date and time
                'successful' => 0,  // Failed login attempt
            ];

            // SAVE LOG DATA TO DB
            $this->AuthModel->LogLogin($logData);  // Assuming you have the createLoginLog method in the model
        }
    }

     /**
     * --------------------------------------------------------------------------
     * REMEMBER ME
     * --------------------------------------------------------------------------
     *
     * if the remember me function is set to true in the config file
     * we set up a cookie using a secure selector|validator
     *
     * @param  int $userID
     * @return void
    */

    public function rememberMe($userID)
    {
        // Check if Remember Me is enabled
        if (!$this->config['rememberMe']['enabled']) {
            return;
        }

        // Generate secure tokens
        $selector = Str::random(12);
        $validator = Str::random(20);
        $expires = Carbon::now()->addDays($this->config['rememberMe']['expire_days']);

        // Hash the validator
        $hashedValidator = hash('sha256', $validator);

        // Prepare the token
        $token = $selector . ':' . $validator;

        $data = [
            'user_id' => $userID,
            'selector' => $selector,
            'hashedvalidator' => $hashedValidator,
            'expires' => $expires,
        ];

        // CHECK IF A USER ID ALREADY HAS A TOKEN SET
        //
        // We dont really want to have multiple tokens and selectors for the
        // same user id. there is no need as the validator gets updated on each login
        // so check if there is a token already and overwrite if there is.
        // should keep DB maintenance down a bit and remove the need to do sporadic purges.
        //

        $result = $this->AuthModel->GetAuthTokenByUserId($userID);
        // IF NOT INSERT
        if (empty($result)) {
            $this->AuthModel->insertToken($data);
        } else {
            $this->AuthModel->updateToken($data);
        }

          // Set the cookie
        Cookie::queue(
            'remember',
            $token,
            $expires->diffInMinutes(),
            '/',
            config('session.domain', null),
            config('session.secure', false),
            true // HTTP-only
        );
    }

    /**
     * --------------------------------------------------------------------------
     * CHECK REMEMBER ME COOKIE
     * --------------------------------------------------------------------------
     *
     * checks to see if a remember me cookie has ever been set
     * if we find one w echeck it against our auth_tokens table and see
     * if we find a match and its still valid.
     *
     * @return void
    */

    public function checkCookie()
    {
        // Check if the user is locked out
        if (Session::get('lockscreen') == true) {
            return;
        }

        // Check if a remember me cookie is set
        $remember = Cookie::get('remember');

        // No cookie found, return
        if (empty($remember)) {
            return;
        }

        // Extract the selector and validator from the cookie
        list($selector, $validator) = explode(':', $remember);
        $validator = hash('sha256', $validator);

        // Get the token from the database using the selector
        $token = AuthToken::where('selector', $selector)->first();

        // If no token is found, return
        if (empty($token)) {
            return false;
        }

         // If the hash of the validator does not match, return
         if (!hash_equals($token->hashedvalidator, $validator)) {
            return false;
        }

        // If a match is found, get the user
        $user = User::find($token->user_id);

        // If no user is found, return
        if (empty($user)) {
            return false;
        }

        // If a forced login is enabled, randomly decide whether to delete the token
        if (config('auth.forceLogin') > 1) {
            if (rand(1, 100) < config('auth.forceLogin')) {
                $this->deleteToken($token->user_id);
                return;
            }
        }

        // Set the user session
        $this->setUserSession($user);

        // Reset the remember me cookie
        $this->rememberMeReset($user->id, $selector);

        return;
    }

    /**
     * Example Method - Send Welcome Email
     *
     * Send a welcome email to the user after successful registration.
     *
     * @param string $email
     * @return void
     */
    public function sendWelcomeEmail($email)
    {
        // Use the SendEmail library to send an email
        $this->sendEmail->send($email, 'Welcome to our application', 'welcome-email-template');
    }

     /**
     * --------------------------------------------------------------------------
     * LOGOUT
     * --------------------------------------------------------------------------
     *
     * @return void
     */
    public function logout() {
        // REMOVE REMEMBER ME TOKEN FROM DB
        $this->AuthModel->DeleteTokenByUserId($this->session->get('id'));
        //DESTROY SESSION
        Session::flush();
        return;
    }



    public function autoredirect()
    {
        // Load redirection configuration
        $redirect = $this->config['assign_redirect'];
        // Get user role from session
        $role = $this->session->get('role');

        // Check if the role exists in the redirection map
        if (isset($redirect[$role])) {
            return $redirect[$role]; // Return the mapped URL for the role
        }

        // Fallback URL if role is not mapped
        return '/default-page';
    }
}
