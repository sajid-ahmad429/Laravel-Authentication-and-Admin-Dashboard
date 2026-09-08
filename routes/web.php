<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SystemHealthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WelcomeEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Public authentication routes are rate limited on POST. Every admin /
| management route requires an authenticated session, and mutations plus
| role/permission management additionally require an admin-level role.
|
*/

// Public: landing & login form.
Route::get('/', [AuthController::class, 'index'])->name('home');
Route::get('sysLogin', [AuthController::class, 'index'])->name('sys.login');

// Public: authentication (POST endpoints are rate limited).
Route::get('sysCtrlLogin', [AuthController::class, 'login'])->name('login');
Route::post('sysCtrlLogin', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('register', [AuthController::class, 'register'])->name('register');
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::get('forgotpassword', [AuthController::class, 'forgotPassword'])->name('forgotpassword');
Route::post('forgotpassword', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-email');
Route::get('/resetpassword/{id}/{token}', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::get('activate/{id}/{token}', [AuthController::class, 'activateUser'])->name('activate.user');
Route::get('resend-activation/{id}', [AuthController::class, 'resendActivation'])
    ->name('resend.activation')
    ->middleware('throttle:password-email');
Route::get('/updatepassword/{id}', [AuthController::class, 'updatePassword'])->name('password.update');
Route::post('/updatepassword/{id}', [AuthController::class, 'updatePassword'])->middleware('throttle:password-email');

// Authenticated utility: resend an activation link from the admin user list (AJAX).
Route::post('send-activation-link/{value}', [AuthController::class, 'sendActivationLink'])
    ->name('send.link')
    ->middleware(['auth.session', 'role:superadmin,admin', 'throttle:password-email']);

// Logout is POST-only (CSRF protected) to prevent logout-CSRF attacks.
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Status workflow: POST-only, authenticated admins only.
Route::post('chnage_status', [CommonController::class, 'chnage_status'])->name('status.change')
    ->middleware(['auth.session', 'role:superadmin,admin']);

// Welcome e-mail utility: authenticated + rate limited.
Route::post('send-email', [WelcomeEmailController::class, 'sendEmail'])->name('send-email')
    ->middleware(['auth.session', 'throttle:password-email']);

// ==========================================
// Role-Based Admin & Management Panels
// ==========================================
$roles = ['superadmin', 'admin', 'author', 'maintainer', 'editor', 'subscriber'];

foreach ($roles as $role) {
    Route::prefix($role)->name("{$role}.")->middleware('auth.session')->group(function () {
        // Dashboard landing (any authenticated user).
        Route::get('/', [AuthController::class, 'countList'])->name('dashboard');

        // User directory (read access for editors and above).
        Route::get('/users', [UserController::class, 'index'])->name('users.index')
            ->middleware('role:superadmin,admin,editor');
        Route::post('/users/registry-data', [UserController::class, 'getTableData'])->name('users.data')
            ->middleware('role:superadmin,admin,editor');
        Route::post('/users/profile-details', [UserController::class, 'getUserDetails'])->name('users.details')
            ->middleware('role:superadmin,admin,editor');

        // User mutations (admin-level only).
        Route::post('/users/persistence-store', [UserController::class, 'store'])->name('users.store')
            ->middleware('role:superadmin,admin');
        Route::post('/users/trash-toggle', [UserController::class, 'toggleTrash'])->name('users.toggleTrash')
            ->middleware('role:superadmin,admin');

        // Roles & Permissions management (admin-level only).
        Route::resource('roles', RoleController::class)->only(['index', 'create', 'store', 'destroy'])
            ->middleware('role:superadmin,admin');
        Route::post('/roles/data', [RoleController::class, 'getTableData'])->name('roles.data')
            ->middleware('role:superadmin,admin');
        Route::resource('permissions', PermissionController::class)->only(['index', 'store', 'destroy'])
            ->middleware('role:superadmin,admin');
        Route::post('/permissions/data', [PermissionController::class, 'getTableData'])->name('permissions.data')
            ->middleware('role:superadmin,admin');

        // Audit log (admin-level only).
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity_logs.index')
            ->middleware('role:superadmin,admin');
        Route::get('/activity-logs/data', [ActivityLogController::class, 'getLogsData'])->name('activity_logs.data')
            ->middleware('role:superadmin,admin');

        // SaaS & self-service pages (any authenticated user).
        Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

        // System internals (admin-level only).
        Route::get('/health', [SystemHealthController::class, 'index'])->name('health.index')
            ->middleware('role:superadmin,admin');
    });
}

// ==========================================
// Diagnostics (local/staging + admins only)
// ==========================================
if (app()->environment(['local', 'staging', 'testing'])) {
    Route::prefix('diagnostics')->middleware(['auth.session', 'role:superadmin,admin'])->group(function () {
        Route::get('/test-job', [DiagnosticsController::class, 'testJob'])->name('diagnostics.job');
        Route::get('/test-email', [DiagnosticsController::class, 'testEmail'])->name('diagnostics.email');
        Route::get('/send-reset-email', [DiagnosticsController::class, 'sendResetEmail'])->name('diagnostics.reset-email');
    });
}
