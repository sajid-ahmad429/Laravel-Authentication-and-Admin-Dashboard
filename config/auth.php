<?php

return [
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
    | Define the default role ID for new user registrations.
    |
    */

    'default_role' => 'admin',

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
