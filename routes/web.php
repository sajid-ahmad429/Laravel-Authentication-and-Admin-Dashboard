<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SystemHealthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Hardened Production Setup
|--------------------------------------------------------------------------
|
| Conventions enforced in this file:
|  - Every state changing action is POST only (CSRF protected, no GET mutations).
|  - Sensitive endpoints are rate limited.
|  - All authenticated pages live behind `auth.session` (fail-closed) and
|    `role.access:{feature}` rank checks.
|  - Admin features are mounted once under a role `{panel}` prefix instead of
|    being duplicated for every role (previous versions exposed every admin
|    capability to every role).
|
*/

// ---------------------------------------------------------------------------
// Public authentication routes
// ---------------------------------------------------------------------------
Route::controller(AuthController::class)->group(function () {
    Route::get('/', 'index')->name('home');

    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->middleware('throttle:login')->name('login.attempt');

    Route::get('/register', 'showRegister')->name('register');
    Route::post('/register', 'register')->middleware('throttle:register')->name('register.attempt');

    Route::get('/forgotpassword', 'showForgotPassword')->name('forgotpassword');
    Route::post('/forgotpassword', 'forgotPassword')->middleware('throttle:reset')->name('forgotpassword.attempt');

    Route::get('/resetpassword/{id}/{token}', 'resetPassword')
        ->where(['id' => '[A-Za-z0-9+/=]+', 'token' => '[A-Za-z0-9+/=]+'])
        ->name('password.reset');

    Route::get('/newpassword', 'showResetPasswordForm')->name('password.reset.form');

    Route::post('/updatepassword', 'updatePassword')
        ->middleware('throttle:reset')
        ->name('password.update');

    Route::get('/activate/{id}/{token}', 'activateUser')
        ->where(['id' => '[A-Za-z0-9+/=]+', 'token' => '[A-Za-z0-9+/=]+'])
        ->middleware('throttle:reset')
        ->name('activate.user');

    Route::post('/resend-activation', 'resendActivation')
        ->middleware('throttle:reset')
        ->name('resend.activation');

    Route::post('/logout', 'logout')->name('logout');
});

// Legacy aliases kept for backwards compatibility of bookmarks / emails.
Route::redirect('/sysLogin', '/login', 301);
Route::redirect('/sysCtrlLogin', '/login', 301);

// ---------------------------------------------------------------------------
// Authenticated admin area (single mount, role-aware panel prefix)
// ---------------------------------------------------------------------------

Route::prefix('{panel}')
    ->whereIn('panel', array_keys(config('auth.roles')))
    ->middleware(['auth.session', 'role.access'])
    ->group(function () {
        Route::get('/', [AuthController::class, 'dashboard'])->name('panel.dashboard');

        // User management (editor and above)
        Route::get('/users', [UserController::class, 'index'])->middleware('role.access:users')->name('panel.users.index');
        Route::post('/users/data', [UserController::class, 'getTableData'])->middleware('role.access:users')->name('panel.users.data');
        Route::post('/users/store', [UserController::class, 'store'])->middleware('role.access:users')->name('panel.users.store');
        Route::post('/users/details', [UserController::class, 'getUserDetails'])->middleware('role.access:users')->name('panel.users.details');
        Route::post('/users/trash-toggle', [UserController::class, 'toggleTrash'])->middleware('role.access:users')->name('panel.users.trash');
        Route::post('/users/status', [CommonController::class, 'changeStatus'])->middleware('role.access:users')->name('panel.users.status');
        Route::post('/users/resend-activation', [UserController::class, 'resendActivation'])->middleware('role.access:users')->name('panel.users.resend');

        // Access control (admin and above)
        Route::get('/roles', [RoleController::class, 'index'])->middleware('role.access:roles')->name('panel.roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->middleware('role.access:roles')->name('panel.roles.store');
        Route::post('/roles/data', [RoleController::class, 'getTableData'])->middleware('role.access:roles')->name('panel.roles.data');
        Route::post('/roles/destroy', [RoleController::class, 'destroy'])->middleware('role.access:roles')->name('panel.roles.destroy');

        Route::get('/permissions', [PermissionController::class, 'index'])->middleware('role.access:permissions')->name('panel.permissions.index');
        Route::post('/permissions', [PermissionController::class, 'store'])->middleware('role.access:permissions')->name('panel.permissions.store');
        Route::post('/permissions/data', [PermissionController::class, 'getTableData'])->middleware('role.access:permissions')->name('panel.permissions.data');
        Route::post('/permissions/destroy', [PermissionController::class, 'destroy'])->middleware('role.access:permissions')->name('panel.permissions.destroy');

        // Audit trail (admin and above)
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware('role.access:activity')->name('panel.activity.index');
        Route::post('/activity-logs/data', [ActivityLogController::class, 'getTableData'])->middleware('role.access:activity')->name('panel.activity.data');

        // SaaS / insights (all roles)
        Route::get('/plans', [PlanController::class, 'index'])->middleware('role.access:plans')->name('panel.plans.index');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->middleware('role.access:analytics')->name('panel.analytics.index');

        // System (admin and above for health)
        Route::get('/profile', [ProfileController::class, 'index'])->middleware('role.access:profile')->name('panel.profile.index');
        Route::post('/profile/update', [ProfileController::class, 'update'])->middleware('role.access:profile')->name('panel.profile.update');
        Route::get('/health', [SystemHealthController::class, 'index'])->middleware('role.access:health')->name('panel.health.index');
    });

// ---------------------------------------------------------------------------
// Diagnostics — local environment only, never reachable in production
// ---------------------------------------------------------------------------
if (app()->environment('local')) {
    Route::prefix('diagnostics')->middleware('throttle:6,1')->group(function () {
        Route::get('/queue', function () {
            \App\Jobs\SendWelcomeEmail::dispatch('test@example.com', 'Queue connectivity check');

            return 'Test job dispatched successfully.';
        });
    });
}
