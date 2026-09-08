<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role-based authorization middleware.
 *
 * Usage: ->middleware('role:superadmin,admin')
 *
 * The request is allowed when the authenticated user holds ANY of the given
 * roles. Users with the `superadmin` role bypass every role check.
 * Always combine with `auth.session` (or place after it) so guests are
 * redirected to the login page instead of receiving a 403.
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! session()->has('isLoggedIn') && ! auth()->check()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Unauthenticated. Please log in.',
                ], 401);
            }

            return redirect()->route('login')->with('danger', 'Please log in to continue.');
        }

        // Flatten "role:a,b" style definitions into a clean lowercase list.
        $allowed = collect($roles)
            ->flatMap(fn ($role) => explode(',', (string) $role))
            ->map(fn ($role) => strtolower(trim($role)))
            ->filter()
            ->unique()
            ->values();

        // No roles specified: any authenticated user may pass.
        if ($allowed->isEmpty()) {
            return $next($request);
        }

        $sessionRole = strtolower((string) session('role', ''));
        $user = auth()->user();

        $userRoles = collect([$sessionRole]);
        if ($user) {
            try {
                $userRoles = $userRoles->merge($user->getRoleNames()->map(
                    fn ($name) => strtolower((string) $name)
                ));
            } catch (\Throwable $e) {
                // If role resolution fails, fall back to the session role only.
            }
        }
        $userRoles = $userRoles->filter()->unique();

        // Superadmin bypasses all role checks.
        if ($userRoles->contains('superadmin')) {
            return $next($request);
        }

        if ($userRoles->intersect($allowed)->isNotEmpty()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 0,
                'message' => 'Forbidden. You do not have permission to perform this action.',
            ], 403);
        }

        return redirect()->back()->with('danger', 'Unauthorized access. You do not have permission to view that page.');
    }
}
