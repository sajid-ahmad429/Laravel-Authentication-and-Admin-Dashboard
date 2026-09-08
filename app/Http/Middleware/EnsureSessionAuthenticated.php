<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the visitor has an authenticated (session based) account.
 *
 * The application uses a custom session based authentication layer
 * (see \App\Libraries\AuthLibrary). This middleware is the single
 * fail-closed gate every authenticated route must pass through.
 */
class EnsureSessionAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->get('isLoggedIn')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Authentication required.',
                ], 401);
            }

            return redirect()
                ->route('login')
                ->with('danger', 'Your session has expired. Please sign in again.');
        }

        // The session must reference a real, active, non-trashed account.
        $user = \App\Models\User::find(session('id'));

        if (! $user || (int) $user->status !== 1 || (int) $user->trash !== 0) {
            auth()->logout();
            session()->flush();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Your account is no longer active.',
                ], 401);
            }

            return redirect()->route('login')->with('danger', 'Your account is no longer active.');
        }

        // Keep the resolved user available for the rest of the request.
        $request->attributes->set('sessionUser', $user);

        return $next($request);
    }
}
