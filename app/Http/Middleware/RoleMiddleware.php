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
    public function handle(Request $request, Closure $next, $role = null): Response
    {
        $targetRole = $role ?? $request->route('role') ?? session('role');

        if (!$targetRole) {
            return $next($request);
        }

        // Check if the user is logged in via session or Auth
        if (session()->has('isLoggedIn')) {
            $userRole = strtolower(session('role', ''));
            if ($userRole === strtolower($targetRole) || $userRole === 'superadmin') {
                return $next($request);
            }
        }

        if (auth()->check() && (auth()->user()->hasRole($targetRole) || auth()->user()->hasRole(strtolower($targetRole)) || auth()->user()->hasRole('superadmin'))) {
            return $next($request);
        }

        // Redirect unauthorized users
        return redirect()->route('login')->with('danger', 'Unauthorized access.');
    }
}
