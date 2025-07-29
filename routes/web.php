<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\WelcomeEmailController;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use App\Mail\WelcomeMail;

Route::match(['get', 'post'], 'send-email', [WelcomeEmailController::class, 'sendEmail'])->name('send-email');

// Redirect root URL (/) to the index method of the Auth controller
Route::get('/', [App\Http\Controllers\AuthController::class, 'index']);
Route::get('sysLogin', [AuthController::class, 'index']);
Route::match(['get', 'post'], 'sysCtrlLogin', [AuthController::class, 'login'])->name('login');
Route::match(['get', 'post'], 'register', [AuthController::class, 'register'])->name('register');
Route::match(['get', 'post'], 'forgotpassword', [AuthController::class, 'forgotpassword'])->name('forgotpassword');
Route::get('/resetpassword/{id}/{token}', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::match(['get', 'post'], 'activate/{id}/{token}', [AuthController::class, 'activateUser'])->name('activate.user');
Route::match(['get', 'post'], 'resend-activation/{id}', [AuthController::class, 'resendActivation'])->name('resend.activation');
Route::match(['get', 'post'], 'send-activation-link/{value}', [AuthController::class, 'sendActivationLink'])->name('send.link');
Route::match(['get', 'post'], '/updatepassword/{id}', [AuthController::class, 'updatePassword'])->name('password.update');
Route::match(['get', 'post'], 'logout', [AuthController::class, 'logout'])->name('logout');
Route::match(['get','post'], 'chnage_status', [CommonController::class, 'chnage_status'])->name('status.change');

Route::get('/test-job', function () {
    App\Jobs\TestJob::dispatch('This is a test message.');
    return 'Test job dispatched!';
});


Route::get('/test-email', function () {
    try {
        Mail::raw('Hello World! This is a test email.', function ($message) {
            $message->to('sajidahmad.9005@gmail.com') // Replace with your test email
                ->subject('Test Email');
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

    $resetLink = url('password-reset/sample-token'); // The reset password link (can be dynamic)

    try {
        Mail::to($user->email)->send(new ResetPasswordMail($user, $resetLink));

        return "Email sent successfully to {$user->email}.";
    } catch (\Exception $e) {
        return "Failed to send email: " . $e->getMessage();
    }
});

// Route Group for Superadmin
Route::group(['prefix' => 'superadmin'], function () {
    Route::get('/', [AuthController::class, 'countList'])->name('superadmin.dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('superadmin.users.index');
    Route::match(['get', 'post'], '/add-new-users', [UserController::class, 'store'])->name('superadmin.users.store');
    Route::post('/users/get-details', [UserController::class, 'getUserDetails'])->name('superadmin.users.getDetails');
});

// Route Group for Admin
Route::group(['prefix' => 'admin'], function () {
    Route::get('/', [AuthController::class, 'countList'])->name('admin.dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::match(['get', 'post'], '/add-new-users', [UserController::class, 'store'])->name('admin.users.store');
    Route::post('/users/get-details', [UserController::class, 'getUserDetails'])->name('admin.users.getDetails');
});

// Route Group for Author
Route::group(['prefix' => 'author'], function () {
    Route::get('/', [AuthController::class, 'countList'])->name('author.dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('author.users.index');
    Route::match(['get', 'post'], '/add-new-users', [UserController::class, 'store'])->name('author.users.store');
    Route::post('/users/get-details', [UserController::class, 'getUserDetails'])->name('author.users.getDetails');

});

// Route Group for Maintainer
Route::group(['prefix' => 'maintainer'], function () {
    Route::get('/', [AuthController::class, 'countList'])->name('maintainer.dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('maintainer.users.index');
    Route::match(['get', 'post'], '/add-new-users', [UserController::class, 'store'])->name('maintainer.users.store');
    Route::post('/users/get-details', [UserController::class, 'getUserDetails'])->name('maintainer.users.getDetails');
});

// Route Group for Editor
Route::group(['prefix' => 'editor'], function () {
    Route::get('/', [AuthController::class, 'countList'])->name('editor.dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('editor.users.index');
    Route::match(['get', 'post'], '/add-new-users', [UserController::class, 'store'])->name('editor.users.store');
    Route::post('/users/get-details', [UserController::class, 'getUserDetails'])->name('editor.users.getDetails');
});

// Route Group for Subscriber
Route::group(['prefix' => 'subscriber'], function () {
    Route::get('/', [AuthController::class, 'countList'])->name('subscriber.dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('subscriber.users.index');
    Route::match(['get', 'post'], '/add-new-users', [UserController::class, 'store'])->name('subscriber.users.store');
    Route::post('/users/get-details', [UserController::class, 'getUserDetails'])->name('subscriber.users.getDetails');
});

