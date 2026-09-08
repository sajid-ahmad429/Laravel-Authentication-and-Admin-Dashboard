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
            Mail::to($to)->send(new WelcomeMail($message));
            // SendWelcomeEmail::dispatch($to, $message);
            return response()->json(['message' => 'Email sent successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
