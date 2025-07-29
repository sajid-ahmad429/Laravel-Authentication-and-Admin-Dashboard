<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Config\auth;
use App\Models\AuthModel;
use App\Libraries\AuthLibrary;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use App\Rules\ValidateUser;
use Illuminate\Support\Facades\Hash;
use Validator;
use App\Jobs\SendActivationEmailJob;

class AuthController extends Controller
{

    protected $usersModel;
    protected $AuthModel;
    protected $Auth;
    protected $config;

    public function __construct(){
        $this->authModel = new AuthModel();
        $this->usersModel = new User();
        $this->session = session();
        $this->authLibrary = new AuthLibrary;
        $this->config = config('auth');
    }

    public function index()
    {
        // Redirect to the named route
        return redirect()->to('sysCtrlLogin');
    }

    /*
      |--------------------------------------------------------------------------
      | USER LOGIN
      |--------------------------------------------------------------------------
      |
      | Get post data from login.php view
      | Set and Validate rules
      | Pass over to Library LoginUser
      | If successfull get user details from DB
      | Set user session
      | return true / false
      |
    */

    public function login(Request $request) {
        $viewData['config'] = config('auth');  // Get configuration for the auth
        $viewData['errorMessage'] = '';
        
        // Ensure all required view variables are set
        $viewData['errors'] = session('errors') ?: new \Illuminate\Support\ViewErrorBag();

        // Check if cookie is set
        // $this->authLibrary->checkCookie();

        // Check if user is already logged in, redirect to appropriate page
        if (Session::has('isLoggedIn')) {
            return redirect()->to($this->authLibrary->autoRedirect());
        }

        // Handle POST request
        if ($request->isMethod('post')) {
            $rules = [
                'email' => ['required', 'email', new ValidateUser],  // Using custom rule for email
                'password' => ['required', 'string', new ValidateUser],  // Using custom rule for password
            ];

            // Validate the request
            $validator = Validator::make($request->all(), $rules);

            // Check if validation fails
            if ($validator->fails()) {
                $this->authLibrary->loginlogFail($request->input('email'));  // Log failed login attempt
                return redirect()->back()->withErrors($validator)->withInput();  // Return with errors
            }

            // Get email and remember me values from POST request
            $email = $request->input('email');
            $rememberMe = $request->has('rememberme');

            // Check if user exists and is active
            $user = User::where('email', $email)->first();

            if (!$user || $user->status != 1) {
                $viewData['errorMessage'] = 'Please Contact System Administrator';
            } else {
                // Attempt login via the AuthLibrary
                $this->authLibrary->Loginuser($email, $rememberMe);

                // Redirect based on the user's role
                if (Session::has('role')) {
                    return redirect()->to($this->authLibrary->autoRedirect());
                }
            }
        }

        // Return the login view
        return view('admin.auth.login', $viewData);
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

    public function resendactivation($id) {

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

    public function sendActivationLink($id) {
        $decodedId = base64_decode($id);
        // // PASS TO LIBRARY
        $result = $this->authLibrary->ResendActivation($decodedId);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    // public function sendActivationLink($id)
    // {
    //     $decodedId = base64_decode($id);

    //     $user = AuthModel::find($decodedId);

    //     if (!$user) {
    //         return response()->json(['success' => false, 'message' => 'User not found']);
    //     }
    //     // Dispatch the job to send the activation email
    //    SendActivationEmailJob::dispatch($user);


    //     return response()->json(['success' => true, 'message' => 'Activation email sending initiated']);
    // }

    /*
      |--------------------------------------------------------------------------
      | ACTIVATE USER
      |--------------------------------------------------------------------------
      |
      | Activate user account from email link
      |
    */

    public function activateUser($id, $token) {
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

    public function logout() {
        $this->authLibrary->logout();
        return redirect()->to('/');
    }
}
