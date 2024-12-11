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
        echo $role."Ok";
        exit;
        // Check if the user is logged in and has the required role
        if (Auth::check() && Auth::user()->role == $role) {
            return $next($request);
        }

        // Redirect unauthorized users
        return redirect('/unauthorized'); // Define this route for unauthorized access
    }
}
