<?php

namespace App\Http\Controllers;

use App\Libraries\AuthLibrary;
use App\Models\AuthModel;
use App\Models\User;
use App\Rules\ValidateUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected User $usersModel;
    protected AuthModel $authModel;
    protected AuthLibrary $authLibrary;
    protected array $config;

    /**
     * Session key proving a reset link was verified. The password update form
     * (GET and POST) refuses to run unless this matches the requested id, so
     * attackers cannot reset arbitrary accounts by guessing /updatepassword/{id}.
     */
    public const RESET_SESSION_KEY = 'password_reset_verified_id';

    public function __construct()
    {
        $this->authModel = new AuthModel();
        $this->usersModel = new User();
        $this->authLibrary = new AuthLibrary();
        $this->config = config('auth');
    }

    public function index()
    {
        return redirect()->route('login');
    }

    /**
     * Handle the user login process.
     */
    public function login(Request $request)
    {
        try {
            $viewData['config'] = $this->config;

            // Restore a session from a valid remember-me cookie, if present.
            $this->authLibrary->checkCookie();

            if (Session::has('isLoggedIn')) {
                return redirect()->to($this->authLibrary->autoRedirect());
            }

            if ($request->isMethod('post')) {
                $validator = Validator::make($request->all(), [
                    'email' => ['required', 'email', 'max:255'],
                    'password' => ['required', 'string', 'max:255'],
                ]);

                if ($validator->fails()) {
                    $this->authLibrary->loginlogFail(
                        (string) $request->input('email', 'unknown'),
                        'Validation failed'
                    );

                    return redirect()->back()->withErrors($validator)->withInput($request->except('password'));
                }

                $email = (string) $request->input('email');
                $password = (string) $request->input('password');
                $rememberMe = $request->boolean('rememberme');

                $user = User::where('email', $email)->first();

                if (! $user || ! Hash::check($password, $user->password)) {
                    $this->authLibrary->loginlogFail($email);

                    return redirect()->back()
                        ->withInput($request->except('password'))
                        ->with('danger', __('auth.failed'));
                }

                if ((int) $user->activated !== 1) {
                    $this->authLibrary->loginlogFail($email, 'Account not activated');

                    return redirect()->back()
                        ->withInput($request->except('password'))
                        ->with('danger', __('Your account is not activated. Please check your e-mail for the activation link.'))
                        ->with('resetlink', '<a href="'.route('resend.activation', $user->id).'">Resend Activation Email</a>');
                }

                return $this->authLibrary->loginUser($email, $rememberMe);
            }

            return view('admin.auth.login', $viewData);
        } catch (\Throwable $e) {
            Log::error('Login error: '.$e->getMessage());

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
      | Validates the registration form and delegates creation to AuthLibrary,
      | which assigns the default low-privilege role and sends activation mail.
      |
    */
    public function register(Request $request)
    {
        if ($request->isMethod('post')) {
            $validator = Validator::make($request->all(), [
                'username' => ['required', 'string', 'min:3', 'max:25'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:72',
                    'regex:/[a-z]/',
                    'regex:/[A-Z]/',
                    'regex:/[0-9]/',
                    'regex:/[@$!%*?&]/',
                ],
                'terms' => ['accepted'],
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $created = $this->authLibrary->registerUser([
                'name' => $request->input('username'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ]);

            if ($created) {
                return redirect()->route('login')->with('success', 'Registration successful. Please check your email and activate your account to complete verification.');
            }

            return redirect()->back()->with('danger', 'Failed to register. Please try again.')->withInput();
        }

        return view('admin.auth.register');
    }

    /*
      |--------------------------------------------------------------------------
      | RESEND ACTIVATION EMAIL
      |--------------------------------------------------------------------------
    */
    public function resendActivation($id)
    {
        $decodedId = AuthLibrary::decodeId($id) ?? (is_numeric($id) ? (int) $id : null);

        if ($decodedId === null) {
            return redirect()->route('login')->with('danger', __('auth.error_occurred'));
        }

        $this->authLibrary->resendActivation($decodedId);

        return redirect()->route('login');
    }

    /*
      |--------------------------------------------------------------------------
      | SEND ACTIVATION LINK (AJAX, admin user list)
      |--------------------------------------------------------------------------
      |
      | Responds with a plain "1" on success and "0" on failure to match the
      | existing SweetAlert flow in the admin templates.
      |
    */
    public function sendActivationLink($id)
    {
        $decodedId = AuthLibrary::decodeId($id);

        if ($decodedId === null) {
            return response('0', 422);
        }

        $user = User::find($decodedId);

        if (! $user || (int) $user->activated === 1) {
            return response('0', 422);
        }

        return $this->authLibrary->resendActivation($decodedId)
            ? response('1')
            : response('0', 422);
    }

    /*
      |--------------------------------------------------------------------------
      | ACTIVATE USER
      |--------------------------------------------------------------------------
      |
      | Handles account activation from the e-mail link.
      |
    */
    public function activateUser($id, $token)
    {
        $this->authLibrary->activateUser($id, $token);

        return redirect()->route('login');
    }

    /*
      |--------------------------------------------------------------------------
      | FORGOT PASSWORD
      |--------------------------------------------------------------------------
    */
    public function forgotPassword(Request $request)
    {
        if ($request->isMethod('post')) {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email', 'max:255', new ValidateUser],
            ], [
                'email.required' => __('auth.noUser'),
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $this->authLibrary->forgotPassword((string) $request->input('email'));

            return back()->with('success', __('auth.resetSent'));
        }

        return view('admin.auth.forgotpassword');
    }

    /*
      |--------------------------------------------------------------------------
      | RESET PASSWORD (verify link)
      |--------------------------------------------------------------------------
      |
      | Validates the reset link and, on success, stores a one-time session
      | marker authorizing the password update form for this user only.
      |
    */
    public function resetPassword($id, $token)
    {
        $userId = $this->authLibrary->resetPassword($id, $token);

        if (! $userId) {
            return redirect()->route('login');
        }

        session([self::RESET_SESSION_KEY => (int) $userId]);

        return redirect()->route('password.update', ['id' => $userId]);
    }

    /*
      |--------------------------------------------------------------------------
      | UPDATE PASSWORD
      |--------------------------------------------------------------------------
      |
      | Renders / processes the new-password form. Both GET and POST require
      | the session marker issued by resetPassword(), preventing attackers
      | from resetting arbitrary accounts by guessing the numeric id.
      |
    */
    public function updatePassword(Request $request, $id)
    {
        $verifiedId = session(self::RESET_SESSION_KEY);

        if (! $verifiedId || (int) $id !== (int) $verifiedId) {
            return redirect()->route('login')->with('danger', __('auth.invalidToken'));
        }

        if ($request->isMethod('post')) {
            $validator = Validator::make($request->all(), [
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:72',
                    'regex:/[a-z]/',
                    'regex:/[A-Z]/',
                    'regex:/[0-9]/',
                    'regex:/[@$!%*?&]/',
                ],
                'confirm-password' => ['required', 'same:password'],
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $user = AuthModel::where('id', $verifiedId)->first();

            if (! $user) {
                session()->forget(self::RESET_SESSION_KEY);

                return redirect()->route('login')->with('danger', __('auth.userNotFound'));
            }

            // Plaintext assignment: AuthModel hashes it via its model hook.
            $user->password = (string) $request->input('password');
            $user->reset_expire = null;
            $user->reset_token = null;
            $user->save();

            session()->forget(self::RESET_SESSION_KEY);

            return redirect()->route('login')->with('success', __('auth.resetSuccess'));
        }

        return view('admin.auth.resetpassword', ['id' => $verifiedId]);
    }

    /**
     * Role dashboard landing page.
     */
    public function countList()
    {
        if (! session()->has('isLoggedIn') && ! auth()->check()) {
            return redirect()->route('login');
        }

        $activeMenu = 'dashboard';

        return view('admin.auth.superadmin', compact('activeMenu'));
    }

    /*
      |--------------------------------------------------------------------------
      | LOG USER OUT
      |--------------------------------------------------------------------------
    */
    public function logout()
    {
        $this->authLibrary->logout();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }
}
