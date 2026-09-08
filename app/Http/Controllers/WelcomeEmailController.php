<?php

namespace App\Http\Controllers;

use App\Jobs\SendWelcomeEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WelcomeEmailController extends Controller
{
    /**
     * Queue a welcome e-mail to the currently authenticated user.
     * (Previously this public endpoint mailed a hardcoded address with no
     * authentication, rate limiting, or error sanitization.)
     */
    public function sendEmail(Request $request): JsonResponse
    {
        $to = auth()->user()->email ?? session('email');

        if (empty($to)) {
            return response()->json(['error' => 'No recipient available for the welcome e-mail.'], 422);
        }

        $message = 'Welcome to the Admin Dashboard! Your account is ready.';

        try {
            SendWelcomeEmail::dispatch($to, $message);

            return response()->json(['message' => 'Welcome e-mail queued successfully!']);
        } catch (\Throwable $e) {
            Log::error('Failed to queue welcome e-mail: '.$e->getMessage());

            return response()->json(['error' => 'Could not send the e-mail. Please try again later.'], 500);
        }
    }
}
