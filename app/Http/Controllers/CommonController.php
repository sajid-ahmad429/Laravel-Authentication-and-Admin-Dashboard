<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CommonController extends Controller
{
    /**
     * Change a record's status (activate / deactivate / move to trash).
     *
     * Hardened: POST-only route behind auth.session + role middleware, strict
     * validation, users-table only (other whitelisted tables lack the status/
     * trash columns), reversible soft-delete instead of permanent deletion,
     * and protection for the actor's own account plus superadmin accounts.
     */
    public function chnage_status(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', 'max:32'],
            'status' => ['required', 'in:0,1,2'],
            'name' => ['required', 'string', 'max:32'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => '0',
                'message' => 'Invalid request parameters.',
            ], 422);
        }

        $id = base64_decode($request->input('id'), true);
        $tableName = base64_decode($request->input('name'), true);
        $status = (int) $request->input('status');

        if ($id === false || ! ctype_digit((string) $id)) {
            return response()->json(['success' => '0', 'message' => 'Invalid record reference.'], 422);
        }

        // Only the users table carries the status/trash workflow columns.
        if ($tableName !== 'users') {
            return response()->json(['success' => '0', 'message' => 'Unauthorized table operation.'], 403);
        }

        $previous = DB::table($tableName)->where('id', $id)->first();

        if (! $previous) {
            return response()->json(['success' => '0', 'message' => 'Record not found.'], 404);
        }

        $previousUpdateData = (array) $previous;

        // Nobody can change the status of their own account here.
        if ((int) $id === (int) session('id', 0)) {
            return response()->json(['success' => '0', 'message' => 'You cannot change the status of your own account.'], 403);
        }

        // Only a superadmin may change the status of a superadmin account.
        if ($this->isSuperAdminRecord($previous) && strtolower((string) session('role', '')) !== 'superadmin') {
            return response()->json(['success' => '0', 'message' => 'Only a superadmin can modify a superadmin account.'], 403);
        }

        try {
            if ($status === 2) {
                // Reversible soft-delete: mark trashed instead of destroying data.
                $result = DB::table($tableName)->where('id', $id)->update([
                    'status' => 2,
                    'trash' => 1,
                ]);
                $data = array_merge($previousUpdateData, [
                    'status' => 2,
                    'trash' => 1,
                    'status_change_by' => session('id'),
                ]);
                track_activity($previousUpdateData, '', $data, $id, $tableName, 4);
                $message = $result ? 'The record has been moved to trash.' : 'Failed to move the record to trash.';
            } else {
                $result = DB::table($tableName)->where('id', $id)->update(['status' => $status]);
                $data = array_merge($previousUpdateData, [
                    'status' => $status,
                    'status_change_by' => session('id'),
                ]);
                track_activity($previousUpdateData, '', $data, $id, $tableName, $status === 0 ? 3 : 2);
                $message = $result ? 'The record has been updated successfully.' : 'Failed to update the record.';
            }

            // Keep the cached counters truthful.
            if ($result) {
                foreach (['count_active', 'count_inactive', 'count_total', 'users_all_count', 'users_active_count', 'users_inactive_count', 'analytics_summary_metrics'] as $key) {
                    \Illuminate\Support\Facades\Cache::forget($key);
                }
                \Illuminate\Support\Facades\Cache::forget("user_details_{$id}");
            }

            return response()->json([
                'success' => $result ? '1' : '0',
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            Log::error('Status change failed: '.$e->getMessage(), ['table' => $tableName, 'id' => $id]);

            return response()->json([
                'success' => '0',
                'message' => 'Could not update the record. Please try again.',
            ], 500);
        }
    }

    /**
     * Check whether a users-table row belongs to a superadmin.
     */
    protected function isSuperAdminRecord(object $row): bool
    {
        if (strtolower((string) ($row->roles ?? '')) === 'superadmin') {
            return true;
        }

        try {
            $user = User::find($row->id);

            return $user ? $user->hasRole('superadmin') : false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
