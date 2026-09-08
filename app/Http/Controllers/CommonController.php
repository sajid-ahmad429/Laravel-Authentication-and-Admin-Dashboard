<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Status toggle endpoint for the user list.
 *
 * Hardened: single purpose (users only), whitelisted status values,
 * self/protected-account guard, and every write goes through Eloquent so
 * model events (cache invalidation + audit) always fire.
 */
class CommonController extends Controller
{
    public function changeStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id'     => ['required', 'integer', 'min:1'],
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $user = User::find((int) $validated['id']);

        if (! $user) {
            return response()->json(['status' => 0, 'message' => 'User not found.'], 404);
        }

        if ($user->id === (int) session('id')) {
            return response()->json(['status' => 0, 'message' => 'You cannot change your own status.'], 403);
        }

        $actor = $request->attributes->get('sessionUser') ?? User::find(session('id'));

        if (! $actor?->isProtected() && $user->roleRank() >= $actor?->roleRank()) {
            return response()->json(['status' => 0, 'message' => 'You cannot modify this account.'], 403);
        }

        try {
            $previous = (int) $user->status;

            $user->forceFill(['status' => (int) $validated['status']])->save();

            track_activity(
                ['status' => $previous],
                $user,
                ['status' => (int) $validated['status']],
                $user->id,
                'users',
                2
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json(['status' => 0, 'message' => 'Status update failed.'], 500);
        }

        return response()->json([
            'status'  => 1,
            'message' => (int) $validated['status'] === 1
                ? 'User activated successfully.'
                : 'User deactivated successfully.',
        ]);
    }
}
