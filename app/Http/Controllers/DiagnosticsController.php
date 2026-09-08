<?php

namespace App\Http\Controllers;

use App\Jobs\SendWelcomeEmail;
use App\Mail\ResetPasswordMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Development / staging diagnostics.
 *
 * Every action is gated to non-production environments (see routes) and
 * requires an authenticated superadmin/admin session. No personal addresses
 * are hardcoded; test mail goes to the currently authenticated user.
 */
class DiagnosticsController extends Controller
{
    /**
     * Block diagnostics outside local/staging, even if routes get cached.
     */
    protected function ensureLocal()
    {
        if (! app()->environment(['local', 'staging', 'testing'])) {
            abort(404);
        }
    }

    public function testJob(Request $request)
    {
        $this->ensureLocal();

        $email = auth()->user()->email ?? session('email');

        if (empty($email)) {
            return response('No authenticated user e-mail available for the test job.', 422);
        }

        SendWelcomeEmail::dispatch($email, 'This is a test queue message.');

        return response('Test job dispatched successfully to '.$email);
    }

    public function testEmail(Request $request)
    {
        $this->ensureLocal();

        $email = auth()->user()->email ?? session('email');

        if (empty($email)) {
            return response('No authenticated user e-mail available for the test e-mail.', 422);
        }

        try {
            Mail::raw('Hello! This is a test email from the Admin Dashboard diagnostics.', function ($message) use ($email) {
                $message->to($email)->subject('Test Email');
            });

            return response('Test e-mail queued successfully to '.$email);
        } catch (\Throwable $e) {
            Log::error('Diagnostics test e-mail failed: '.$e->getMessage());

            return response('Failed to send test e-mail. Please check the mail configuration.', 500);
        }
    }

    public function sendResetEmail(Request $request)
    {
        $this->ensureLocal();

        $user = auth()->user();

        if (! $user) {
            return response('No authenticated user available for the reset e-mail preview.', 422);
        }

        $resetLink = url('password-reset/sample-token');

        try {
            Mail::to($user->email)->send(new ResetPasswordMail($user, $resetLink));

            return response('Reset preview e-mail sent successfully to '.$user->email);
        } catch (\Throwable $e) {
            Log::error('Diagnostics reset e-mail failed: '.$e->getMessage());

            return response('Failed to send reset preview e-mail. Please check the mail configuration.', 500);
        }
    }
}
