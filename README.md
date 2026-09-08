# Laravel Authentication & Admin Dashboard

A Laravel 11 admin starter with custom session authentication, account
activation + password-reset e-mails, remember-me cookies, login auditing,
activity logging, Spatie roles & permissions, server-side DataTables user
management, SaaS analytics pages, and a system-health dashboard.

## Requirements

- PHP 8.2+, Composer
- SQLite (default), MySQL, or PostgreSQL
- Node 18+ (only for rebuilding frontend assets)

## Quick start

```bash
cp .env.example .env
composer install
php artisan key:generate

# SQLite (default)
touch database/database.sqlite
php artisan migrate --seed

php artisan storage:link   # avatar uploads
php artisan serve
```

Open `http://127.0.0.1:8000/sysCtrlLogin`.

### Seeded super admin

`SuperAdminSeeder` reads its credentials from the environment (never from git):

```env
SUPERADMIN_NAME="Super Admin"
SUPERADMIN_EMAIL=superadmin@example.com
SUPERADMIN_PASSWORD=   # empty => a random password is generated + printed once
```

Change the password immediately after the first login (`/<role>/profile`).

## Roles & access model

| Area | Who |
|---|---|
| Dashboards, plans, analytics, own profile | any authenticated user |
| User list / details / table data | `superadmin`, `admin`, `editor` |
| Create/update/trash users, status workflow | `superadmin`, `admin` |
| Roles & permissions management | `superadmin`, `admin` |
| Activity logs, system health | `superadmin`, `admin` |

Enforcement lives in `app/Http/Middleware/AuthenticateSession.php`
(`auth.session`) and `app/Http/Middleware/RoleMiddleware.php` (`role`),
applied in `routes/web.php`. Only a `superadmin` can grant the `superadmin`
role or modify another superadmin account, and nobody can trash/deactivate
their own account through the management UIs.

Public registration always assigns the low-privilege `subscriber` role
(`config/auth.php` → `default_role`); admin-created users are pre-activated
with a random one-time password returned once in the create response.

## Authentication notes

- Custom session auth (`App\Libraries\AuthLibrary`) bridged into Laravel's
  guard via `Auth::login()`, so `Auth::user()`, `@auth`, and Spatie
  `@role` / `@can` directives all work.
- Activation & password-reset links carry a random URL-safe token; only a
  bcrypt hash is stored. The password-update form additionally requires a
  one-time session marker issued when the reset link is verified.
- Remember-me uses the selector + hashed-validator pattern with rotating
  tokens stored in `auth_tokens`.
- Login, registration, and all e-mail-sending endpoints are rate limited
  (see `AppServiceProvider::boot()`).
- Logout is POST-only with CSRF protection.

## Useful commands

```bash
php artisan test                  # PHPUnit (SQLite in-memory)
php artisan app:optimize --clear  # cache config/routes/views/events
php artisan app:optimize --migrate --detailed
php artisan queue:work            # process mailed jobs (database queue)
npm run build                     # rebuild Tailwind/Vite assets
```

## Diagnostics (local/staging only)

`/diagnostics/test-job`, `/diagnostics/test-email`, and
`/diagnostics/send-reset-email` are only registered in `local`, `staging`,
and `testing` environments and require a superadmin/admin session. Test mail
is sent to the currently authenticated user.

## Security reporting

If you discover a security vulnerability, please open a private security
advisory on GitHub instead of a public issue.
