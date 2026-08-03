<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AuthModel;
use App\Libraries\AuthLibrary;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Rules\ValidateUser;
use App\Jobs\SendActivationEmailJob;

class AuthController extends Controller
{
    protected $usersModel;
    protected $authModel;
    protected $session;
    protected $authLibrary;
    protected $config;

    public function __construct()
    {
        $this->authModel = new AuthModel();
        $this->usersModel = new User();
        $this->session = session();
        $this->authLibrary = new AuthLibrary();
        $this->config = config('auth');
    }

    public function index()
    {
        // Redirect to the named login route if defined, else URL path
        return redirect()->to('sysCtrlLogin');
    }

    /**
     * Handle User Login Process (Production Grade)
     */
    public function login(Request $request)
    {
        try {
            $viewData['config'] = $this->config;
            $viewData['errorMessage'] = '';

            // 1. Check and process Remember Me cookie if present
            $this->authLibrary->checkCookie();

            // 2. Redirect if already authenticated based on role
            if (Session::has('isLoggedIn')) {
                return redirect()->to($this->authLibrary->autoRedirect());
            }

            // 3. Handle POST request
            if ($request->isMethod('post')) {
                $rules = [
                    'email'    => ['required', 'email'],
                    'password' => ['required', 'string'],
                ];

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    $this->authLibrary->loginlogFail($request->input('email', 'unknown'));
                    return redirect()->back()->withErrors($validator)->withInput($request->except('password'));
                }

                $email = $request->input('email');
                $password = $request->input('password');
                $rememberMe = $request->has('rememberme');

                // 4. Fetch user securely
                $user = User::where('email', $email)->first();

                // 5. Security check: Validate user existence, password match, and activation status
                if (!$user || !Hash::check($password, $user->password) || $user->activated != 1) {
                    $this->authLibrary->loginlogFail($email);

                    return redirect()->back()
                        ->withInput($request->except('password'))
                        ->with('danger', __('auth.failed') ?: 'Invalid credentials or account not activated.');
                }

                // 6. Attempt login via AuthLibrary (Manages session and redirection)
                return $this->authLibrary->Loginuser($email, $rememberMe);
            }

            // Return the secure login view
            return view('admin.auth.login', $viewData);
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput($request->except('password'))
                ->with('danger', 'An unexpected error occurred. Please try again later.');
        }
    }

    /*
      |--------------------------------------------------------------------------
      | REGISTER USER
      |--------------------------------------------------------------------------
      |
      | Get post data from register.php view
      | Set and Validate rules
      | pass over to library RegisterUser
      | If successfull save user details to DB
      | check if we should send activation email
      | return true / false
      |
    */

    // User Registration Method
    public function register(Request $request)
    {
        if ($request->isMethod('post')) {
            // Define Validation Rules
            $rules = [
                'username' => 'required|min:3|max:25',
                'email' => 'required|email|unique:users,email',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/[a-z]/',       // At least one lowercase letter
                    'regex:/[A-Z]/',       // At least one uppercase letter
                    'regex:/[0-9]/',       // At least one number
                    'regex:/[@$!%*?&]/',   // At least one special character
                ],
            ];

            // Validate the request
            $validator = Validator::make($request->all(), $rules);

            // Check if validation fails
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();  // Return with errors
            }

            // Set User Data
            $userData = [
                'name' => $request->input('username'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ];

            // Save User to Database
            $user = $this->authLibrary->RegisterUser($userData);

            // Check If User is Created Successfully
            if ($user) {
                return redirect()->route('login')->with('success', 'Registration successful. Please check your email and activate your account to complete verification.');
            } else {
                return back()->with('error', 'Failed to register. Please try again.');
            }
        }

        return view('admin.auth.register');
    }

    /*
      |--------------------------------------------------------------------------
      | RESEND ACTIVATION EMAIL
      |--------------------------------------------------------------------------
      |
      | If user needs to resend activation email
      |
     */

    public function resendactivation($id)
    {

        // PASS TO LIBRARY
        $this->authLibrary->ResendActivation($id);

        return redirect()->to('sysCtrlLogin');
    }

    /*
      |--------------------------------------------------------------------------
      | RESEND ACTIVATION EMAIL
      |--------------------------------------------------------------------------
      |
      | If user needs to resend activation email
      |
     */

    public function sendActivationLink($id)
    {
        $decodedId = base64_decode($id);
        // // PASS TO LIBRARY
        $result = $this->authLibrary->ResendActivation($decodedId);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    
    /*
      |--------------------------------------------------------------------------
      | ACTIVATE USER
      |--------------------------------------------------------------------------
      |
      | Activate user account from email link
      |
    */

    public function activateUser($id, $token)
    {
        // PASS TO LIBRARY
        $this->authLibrary->activateuser($id, $token);
        return redirect()->to('/');
    }

    /*
      |--------------------------------------------------------------------------
      | REGISTER USER
      |--------------------------------------------------------------------------
      |
      | Get post data from forgotpassword.php view
      | Set and Validate rules
      | Save to DB
      | Set session data
      |
    */

    public function forgotPassword(Request $request)
    {
        if ($request->isMethod('post')) {
            // SET UP VALIDATION RULES
            $rules = [
                'email' => ['required', 'email', new ValidateUser],
            ];

            // SET UP CUSTOM ERROR MESSAGES
            $messages = [
                'email.exists' => __('auth.noUser'), // Equivalent to lang('Auth.noUser')
            ];

            // VALIDATE REQUEST
            $validator = Validator::make($request->all(), $rules, $messages);

            // CHECK VALIDATION
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            } else {
                $this->authLibrary->ForgotPassword($request->input('email'));
            }
        }
        // RENDER THE VIEW
        return view('admin.auth.forgotpassword');
    }

    /*
      |--------------------------------------------------------------------------
      | RESET PASSWORD
      |--------------------------------------------------------------------------
      |
      | Takes the response from a a rest link from users reset email
      | Pass the user id and token to Library resetPassword();
      |
    */

    public function resetPassword($id, $token)
    {
        // PASS TO LIBRARY
        $id = $this->authLibrary->resetPassword($id, $token);

        // Redirect to the updatePassword route
        return redirect()->route('password.update', ['id' => $id]);
    }


    /*
      |--------------------------------------------------------------------------
      | UPDATE PASSWORD
      |--------------------------------------------------------------------------
      |
      | Get post data from resetpassword.php view
      | Save new password to DB
      |
    */

    public function updatePassword(Request $request, $id)
    {
        // Check if the method is POST
        if ($request->isMethod('post')) {
            // Set validation rules
            $rules = [
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/[a-z]/',       // At least one lowercase letter
                    'regex:/[A-Z]/',       // At least one uppercase letter
                    'regex:/[0-9]/',       // At least one number
                    'regex:/[@$!%*?&]/',   // At least one special character
                ],
                'confirm-password' => 'required|same:password', // Ensure passwords match
            ];

            // Validate the request
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // Validation passed, update the user password
            $user = AuthModel::where('id', $id)->first();

            if (!$user) {
                return redirect()->back()->with('danger', __('User not found.'));
            }

            $user->password = $request->input('password');
            $user->reset_expire = null; // Clear reset expiry
            $user->reset_token = null;  // Clear reset token
            $user->save();

            return redirect()->route('login')->with('success', __('Password updated successfully.'));
        }

        // Render the password reset view
        return view('admin.auth.resetpassword', ['id' => $id]);
    }


    public function countList()
    {
        // Check if user is logged in
        if (!session()->has('isLoggedIn')) {
            return redirect()->to('sysCtrlLogin');
        }
        // Active menu for highlighting in the view
        $activeMenu = 'dashboard';

        // Fetch the list of users
        $users = User::all(); // Fetch all users from the `users` table

        // Pass data to the view
        return view('admin.auth.superadmin', compact('activeMenu', 'users'));
    }

    /*
      |--------------------------------------------------------------------------
      | LOG USER OUT
      |--------------------------------------------------------------------------
      |
      | Destroy session
      |
     */

    public function logout()
    {
        $this->authLibrary->logout();
        return redirect()->to('/');
    }
}
