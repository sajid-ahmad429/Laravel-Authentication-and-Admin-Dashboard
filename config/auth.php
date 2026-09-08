<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of seconds before a password confirmation
    | window expires. If the timeout is exceeded, users will be redirected to
    | the password confirmation screen when attempting sensitive actions.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    /*
    |--------------------------------------------------------------------------
    | User Roles
    |--------------------------------------------------------------------------
    |
    | Define user roles and their corresponding IDs as per your application's
    | requirements. Ensure these roles match the roles defined in your database.
    |
    */

    'assign_roles' => [
        'Super Admin' => 'superadmin',
        'Admin' => 'admin',
        'Author' => 'author',
        'Maintainer' => 'maintainer',
        'Editor' => 'editor',
        'Subscriber' => 'subscriber',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role-Based Redirects
    |--------------------------------------------------------------------------
    |
    | Define the redirects based on user roles. The key should correspond to
    | the role ID and the value should be the redirect path.
    |
    */

    'assign_redirect' => [
        'superadmin' => '/superadmin',
        'admin' => '/admin',
        'author' => '/author',
        'maintainer' => '/maintainer',
        'editor' => '/editor',
        'subscriber' => '/subscriber',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Role
    |--------------------------------------------------------------------------
    |
    | The default role assigned to new public registrations. This MUST be a
    | low-privilege role. Never set this to an administrative role.
    |
    */

    'default_role' => 'subscriber',

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    |
    | Settings related to email functionalities like account activation
    | and password resets.
    |
    */

    'send_activation_email' => true,
    'activate_email_subject' => 'Activate Your Account',
    'reset_email_subject' => 'Reset Your Password',

    /*
    |--------------------------------------------------------------------------
    | Token Expiry
    |--------------------------------------------------------------------------
    |
    | Define the expiry times for different tokens such as password reset
    | and account activation tokens, in hours.
    |
    */

    'reset_token_expire' => 1,
    'activate_token_expire' => 24,

    /*
    |--------------------------------------------------------------------------
    | Lock Screen Feature
    |--------------------------------------------------------------------------
    |
    | Enable or disable the lock screen feature for dashboard security.
    |
    */

    'lock_screen' => true,

    /*
    |--------------------------------------------------------------------------
    | Remember Me Settings
    |--------------------------------------------------------------------------
    |
    | Enable or disable the remember me feature, and configure its expiry.
    |
    */

    'rememberMe' => [
        'enabled' => true,
        'expire_days' => 30,
        'renew' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Force Login
    |--------------------------------------------------------------------------
    |
    | Configure the chance of forcing a login even with an active "Remember Me"
    | session. Set a value from 0 (disabled) to 100 (always forced).
    |
    */

    'force_login' => 0,

    /*
    |--------------------------------------------------------------------------
    | Password Hashing Algorithm
    |--------------------------------------------------------------------------
    |
    | Define the hashing algorithm used for passwords.
    | Valid options: PASSWORD_DEFAULT, PASSWORD_BCRYPT, PASSWORD_ARGON2I,
    | PASSWORD_ARGON2ID.
    |
    */

    'hash_algorithm' => PASSWORD_DEFAULT,

    /**
     * --------------------------------------------------------------------
     * Activate Email Subject
     * --------------------------------------------------------------------
     *
     * The subject line for the email that is sent when a user registers
     * if the user activation setting is set to true.
     *
     * @var string
     */
    'activateEmailSubject' => 'Activate Your Account',

    /**
     * --------------------------------------------------------------------
     * Reset Email Subject
     * --------------------------------------------------------------------
     *
     * The subject line for the email that is sent when a user resets their password
     * from the forgot password form
     *
     * @var string
     */
    'resetEmailSubject' => 'Reset Your Password',

];
