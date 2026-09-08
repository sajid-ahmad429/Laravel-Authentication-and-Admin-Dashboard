<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require an authenticated session for protected web routes.
 *
 * The application uses a custom session-based login (see AuthLibrary) that
 * also populates Laravel's Auth guard via Auth::login(). Either signal is
 * accepted so both legacy session checks and Auth::check() keep working.
 */
class AuthenticateSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('isLoggedIn') || auth()->check()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthenticated. Please log in.',
            ], 401);
        }

        return redirect()->route('login')->with('danger', 'Please log in to continue.');
    }
}
