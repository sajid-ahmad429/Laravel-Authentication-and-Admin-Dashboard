<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fail-closed role authorization gate.
 *
 * Authorization model
 * -------------------
 * - The authenticated user's role is stored in the session (`session('role')`).
 * - Every role has a numeric rank (see config/auth.php `role_rank`).
 * - A route may declare the minimum feature rank: ->middleware('role.access:users').
 * - Super Admin always passes; every other role must meet the required rank.
 * - If anything is missing or unknown, access is DENIED (fail-closed).
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        $role = strtolower((string) session('role'));

        $ranks   = (array) config('auth.role_rank', []);
        $feature = strtolower((string) $feature);

        // Unknown / missing role => deny.
        if ($role === '' || ! array_key_exists($role, $ranks)) {
            return $this->deny($request, 'You are not authorized to perform this action.');
        }

        // Super Admin bypasses every feature gate.
        $isSuperAdmin = $role === 'superadmin';

        // Panel prefix check: panel must be a KNOWN panel; only Super Admin
        // may browse panels other than their own. Unknown panels are denied.
        if (! $isSuperAdmin && ($panel = strtolower((string) $request->route('panel'))) !== '') {
            $allowedPanels = array_keys($ranks);

            if (! in_array($panel, $allowedPanels, true) || $panel !== $role) {
                return $this->deny($request, 'You are not authorized to access this panel.');
            }
        }

        // Feature rank check.
        if (! $isSuperAdmin && $feature !== '') {
            $required = (int) config("auth.feature_ranks.{$feature}", PHP_INT_MAX);

            if ((int) $ranks[$role] < $required) {
                return $this->deny($request, 'Your role does not have permission to access this area.');
            }
        }

        return $next($request);
    }

    protected function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status'  => 0,
                'message' => $message,
            ], 403);
        }

        // Redirect privileged users to their own dashboard, guests to login.
        if (! session()->get('isLoggedIn')) {
            return redirect()->route('login')->with('danger', $message);
        }

        $fallback = config("auth.assign_redirect." . strtolower((string) session('role')), '/');

        return redirect()->to($fallback)->with('danger', $message);
    }
}
