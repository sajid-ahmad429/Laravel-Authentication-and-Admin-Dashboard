# AdminPro — Laravel Authentication & Admin Dashboard (Production Edition)

A hardened, production-ready admin system: session-based authentication with
email activation, rank-based role authorization, full audit logging and
**Tabulator server-side data tables** — built on Laravel 11 + Spatie
Permission + the Materio (Bootstrap 5) design system.

---

## Security architecture

| Layer | Mechanism |
|---|---|
| Authentication | Custom session auth (`App\Libraries\AuthLibrary`) — session ID regeneration on login/logout, remember-me via selector/validator cookies with **server-side expiry + replay rotation** |
| Page access | `auth.session` middleware — fail-closed, re-validates the account on every request (suspended/trashed users are cut off immediately) |
| Feature access | `role.access:{feature}` — rank ladder (`config/auth.php: role_rank`, `feature_ranks`). Super Admin bypasses; everyone else must meet the rank. Unknown roles/panels **deny** |
| Object access | `UserController::canManageTarget()` — you can only act on accounts of strictly lower rank (an admin cannot tamper with admins or the Super Admin) |
| Role assignment | Registering users always get `subscriber`; managers can only assign roles at or below their own rank |
| Password reset | Email token (SHA-256 hashed, 1 h expiry) → **single-use session-bound grant** → new password. The old "POST a password for any user id" hole is closed and regression-tested |
| CSRF | All mutations are POST-only; logout is a POST form; data APIs require `X-CSRF-TOKEN` |
| Rate limiting | Named limiters for `login` (5/min), `register` (5/10min), `reset` (3/5min), keyed on identity+IP |
| Transport | Security headers middleware (`nosniff`, `X-Frame-Options`, HSTS in production), `noindex` on admin pages |
| Audit | Every mutation writes to `activitymaster` (`track_activity()` helper + `LogsActivity` model trait) |

### Historical vulnerabilities fixed in this fork

1. **Account takeover** — `POST /updatepassword/{id}` accepted any numeric id with no token check.
2. **Universal admin access** — every role prefix (`/subscriber/...` included) exposed all admin pages & data APIs to any logged-in user.
3. **Instant privilege escalation** — public registration defaulted new users to the `admin` role.
4. **Hardcoded shared password** (`Smart@#123`) for admin-created users.
5. **Remember-me cookies never expired** and never rotated the validator.
6. **The `roles` string column shadowed Spatie's `roles()` relation**, silently breaking `hasRole()`/`getRoleNames()` (dropped via data migration).
7. **Audit logger wrote to non-existent columns** — audit trail silently failed.
8. Public endpoints for email bombing (`/send-email`), unauthenticated status/delete (`/chnage_status`), job dispatch and mail tests (diagnostics routes).
9. Broken route names (`admin.roles.destroy`) fatalling the roles table; CSRF bypass via GET mutations; CSRF token leaked in a JSON API.

---

## Server-side tables (Tabulator)

All list screens (Users, Roles, Permissions, Activity Logs) use **Tabulator 6.3
(vendored locally — zero CDN dependencies)** through a shared wrapper
(`public/assets/js/admin-tables.js`):

* Remote pagination, sorting and filtering via a clean Laravel contract:
  `page, size, sort_by, sort_dir, search, status, trash, role`
* Whitelisted sort columns, escaped LIKE search (no wildcard injection)
* Debounced global search, filter chips, page-size selector, CSV/JSON export
* XSS-safe DOM formatters — the server returns **clean JSON, never HTML**
* Responsive collapse layout for phones/tablets; light + dark theme aware
* Structured error recovery (401/403/419/500) with retry

---

## Getting started

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate

php artisan migrate
php artisan db:seed          # roles, permissions + super admin
php artisan db:seed --class=LargeUserSeeder   # optional demo data (1000 users)

php artisan serve
npm run dev
```

The seeder prints the generated Super Admin credentials **once** — or set
`SEED_SUPERADMIN_EMAIL` / `SEED_SUPERADMIN_PASSWORD` in `.env` first.

### Test accounts

After seeding, `php artisan tinker`:

```php
$user = App\Models\User::where('email', env('SEED_SUPERADMIN_EMAIL', 'admin@example.com'))->first();
$user->assignRole('admin'); // or create additional managers via the UI
```

## Production deployment checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` set
- [ ] HTTPS enabled (`SESSION_SECURE_COOKIE=true`, HSTS header auto-applies)
- [ ] `php artisan config:cache route:cache view:cache event:cache`
- [ ] `php artisan queue:work` (or supervisor) — activation/reset emails queue via `ShouldQueue` mailables
- [ ] Scheduler for token pruning: add `Artisan::command`/schedule entry calling `AuthModel::pruneExpiredTokens()` daily
- [ ] Cache store `redis` recommended (table counters + dashboard aggregates are cached with central invalidation)
- [ ] Run the test suite: `php artisan test`

## Key files

```
app/Http/Middleware/      EnsureSessionAuthenticated, RoleMiddleware, SecurityHeaders
app/Libraries/AuthLibrary.php   Login/register/activation/reset/remember-me service
app/Http/Controllers/     Auth, User (Tabulator API), Role, Permission, ActivityLog, Common
public/assets/js/admin-tables.js        AdminTable factory + AdminUI helpers
public/assets/css/tabulator-materio.css Table theme (light/dark)
config/auth.php           role_rank, feature_ranks, assignable_roles, throttle
tests/Feature/            AuthenticationTest, AuthorizationTest (regression guards)
```
