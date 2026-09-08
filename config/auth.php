<?php

return [
    /*
    |----------------------------------------------------------------------
    | Role Definitions & Ranks
    |----------------------------------------------------------------------
    |
    | Roles are ordered from least to most privileged. The numeric rank is
    | used by the `role.access` middleware to authorize feature access.
    | Never lower the rank of `superadmin` or promotion logic breaks.
    |
    */

    'roles' => [
        'subscriber' => 'Subscriber',
        'maintainer' => 'Maintainer',
        'author'     => 'Author',
        'editor'     => 'Editor',
        'admin'      => 'Admin',
        'superadmin' => 'Super Admin',
    ],

    'role_rank' => [
        'subscriber' => 10,
        'author'     => 20,
        'maintainer' => 20,
        'editor'     => 30,
        'admin'      => 40,
        'superadmin' => 100,
    ],

    /*
    |----------------------------------------------------------------------
    | Feature Rank Map
    |----------------------------------------------------------------------
    |
    | Minimum rank required to use an admin feature. Referenced by the
    | `role.access:{feature}` middleware alias. An unknown feature denies
    | access for everyone except Super Admin.
    |
    */

    'feature_ranks' => [
        'dashboard' => 0,
        'profile'   => 0,
        'plans'     => 0,
        'analytics' => 0,
        'users'     => 30,   // editor and above
        'roles'     => 40,   // admin and above
        'permissions' => 40, // admin and above
        'activity'  => 40,   // admin and above (audit)
        'health'    => 40,   // admin and above
    ],

    /*
    |----------------------------------------------------------------------
    | Assignable Roles (User Management)
    |----------------------------------------------------------------------
    |
    | Roles an authenticated manager may assign to other users. `superadmin`
    | is intentionally excluded — only a Super Admin can create Super Admins,
    | and this list is additionally filtered at runtime by the caller.
    |
    */

    'assignable_roles' => ['subscriber', 'author', 'maintainer', 'editor', 'admin'],

    /*
    |----------------------------------------------------------------------
    | System (Protected) Records
    |----------------------------------------------------------------------
    */

    'protected_roles'   => ['superadmin'],
    'protected_plans'   => ['basic', 'professional', 'enterprise', 'company', 'team'],

    /*
    |----------------------------------------------------------------------
    | Role-Based Redirects
    |----------------------------------------------------------------------
    */

    'assign_redirect' => [
        'superadmin' => '/superadmin',
        'admin'      => '/admin',
        'author'     => '/author',
        'maintainer' => '/maintainer',
        'editor'     => '/editor',
        'subscriber' => '/subscriber',
    ],

    /*
    |----------------------------------------------------------------------
    | Registration
    |----------------------------------------------------------------------
    |
    | SECURITY: new public sign-ups always receive the least-privileged role.
    | This used to default to `admin` which allowed instant privilege
    | escalation from the public registration form.
    |
    */

    'default_role' => 'subscriber',

    /*
    |----------------------------------------------------------------------
    | Email / Activation Settings
    |----------------------------------------------------------------------
    */

    'send_activation_email'   => true,
    'activate_email_subject'  => 'Activate Your Account',
    'reset_email_subject'     => 'Reset Your Password',
    'reset_token_expire'      => 1,     // hours
    'activate_token_expire'   => 24,    // hours

    /*
    |----------------------------------------------------------------------
    | Login Throttling
    |----------------------------------------------------------------------
    */

    'throttle' => [
        'login'   => ['max_attempts' => 5, 'decay_seconds' => 60],
        'reset'   => ['max_attempts' => 3, 'decay_seconds' => 300],
        'resend'  => ['max_attempts' => 3, 'decay_seconds' => 300],
    ],

    /*
    |----------------------------------------------------------------------
    | Remember Me Settings
    |----------------------------------------------------------------------
    */

    'rememberMe' => [
        'enabled'     => true,
        'expire_days' => 30,
        'renew'       => true,
    ],

    /*
    |----------------------------------------------------------------------
    | Lock Screen Feature
    |----------------------------------------------------------------------
    */

    'lock_screen' => false,

    'force_login' => 0,

    'hash_algorithm' => PASSWORD_DEFAULT,

    'activateEmailSubject' => 'Activate Your Account',
    'resetEmailSubject'    => 'Reset Your Password',
];
