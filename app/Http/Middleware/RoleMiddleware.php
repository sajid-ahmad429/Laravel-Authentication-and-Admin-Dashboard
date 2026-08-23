<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        // Check if the user is logged in via session or Auth
        if (session()->has('isLoggedIn') && strtolower(session('role')) === strtolower($role)) {
            return $next($request);
        }

        if (auth()->check() && strtolower(auth()->user()->roles) === strtolower($role)) {
            return $next($request);
        }

        // Redirect unauthorized users
        return redirect()->route('login')->with('danger', 'Unauthorized access.');
    }
}
