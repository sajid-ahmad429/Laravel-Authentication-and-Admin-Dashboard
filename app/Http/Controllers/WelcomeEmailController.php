<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;
use App\Jobs\SendWelcomeEmail;

class WelcomeEmailController extends Controller
{
    public function __construct(){

    }

    public function sendEmail(){
        $to = "phpdeveloper.9005@gmail.com";
        $message = "Welcome to Labridge";

        try {
            // Enterprise standard: Always dispatch email to queues to prevent HTTP blocking
            SendWelcomeEmail::dispatch($to, $message);

            return response()->json(['message' => 'Email dispatch queued successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
