<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\WelcomeEmailController;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;

/*
|--------------------------------------------------------------------------
| Web Routes - Enterprise Production Setup
|--------------------------------------------------------------------------
*/

// Public Authentication & Core Routes
Route::controller(AuthController::class)->group(function () {
    Route::get('/', 'index')->name('home');
    Route::get('sysLogin', 'index')->name('sys.login');
    Route::match(['get', 'post'], 'sysCtrlLogin', 'login')->name('login');
    Route::match(['get', 'post'], 'register', 'register')->name('register');
    Route::match(['get', 'post'], 'forgotpassword', 'forgotpassword')->name('forgotpassword');
    Route::get('/resetpassword/{id}/{token}', 'resetPassword')->name('password.reset');
    Route::match(['get', 'post'], 'activate/{id}/{token}', 'activateUser')->name('activate.user');
    Route::match(['get', 'post'], 'resend-activation/{id}', 'resendActivation')->name('resend.activation');
    Route::match(['get', 'post'], 'send-activation-link/{value}', 'sendActivationLink')->name('send.link');
    Route::match(['get', 'post'], '/updatepassword/{id}', 'updatePassword')->name('password.update');
    Route::match(['get', 'post'], 'logout', 'logout')->name('logout');
});

// Common Utilities
Route::match(['get', 'post'], 'chnage_status', [CommonController::class, 'chnage_status'])->name('status.change');
Route::match(['get', 'post'], 'send-email', [WelcomeEmailController::class, 'sendEmail'])->name('send-email');

// ==========================================
// Role-Based Admin & Management Panels
// ==========================================
$roles = ['superadmin', 'admin', 'author', 'maintainer', 'editor', 'subscriber'];

foreach ($roles as $role) {
    Route::prefix($role)->name("{$role}.")->group(function () {
        Route::get('/', [AuthController::class, 'countList'])->name('dashboard');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        
        // Roles & Permissions Management Routes
        Route::resource('roles', RoleController::class)->names('admin.roles')->only(['index', 'create', 'store', 'destroy']);
        Route::resource('permissions', PermissionController::class)->names('admin.permissions')->only(['index', 'store', 'destroy']);

        // Production-Optimized & Clean Named Routes
        Route::match(['get', 'post'], '/users/registry-data', [UserController::class, 'getTableData'])->name('users.data');
        Route::match(['get', 'post'], '/users/persistence-store', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/profile-details', [UserController::class, 'getUserDetails'])->name('users.details');
        Route::post('/users/trash-toggle', [UserController::class, 'toggleTrash'])->name('users.toggleTrash');
    });
}

// ==========================================
// Diagnostics & Testing Routes (Dev/Staging)
// ==========================================
Route::prefix('diagnostics')->group(function () {
    Route::get('/test-job', function () {
        App\Jobs\TestJob::dispatch('This is a test message.');
        return 'Test job dispatched successfully!';
    });

    Route::get('/test-email', function () {
        try {
            Mail::raw('Hello World! This is a test email.', function ($message) {
                $message->to('sajidahmad.9005@gmail.com')->subject('Test Email');
            });
            return "Email sent successfully!";
        } catch (\Exception $e) {
            return "Failed to send email: " . $e->getMessage();
        }
    });

    Route::get('/send-reset-email', function () {
        $user = (object) [
            'name' => 'Sajid Ahmad',
            'email' => 'sajidahmad.9005@gmail.com',
        ];
        $resetLink = url('password-reset/sample-token');

        try {
            Mail::to($user->email)->send(new ResetPasswordMail($user, $resetLink));
            return "Email sent successfully to {$user->email}.";
        } catch (\Exception $e) {
            return "Failed to send email: " . $e->getMessage();
        }
    });
});