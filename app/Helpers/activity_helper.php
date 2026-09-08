<?php

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

if (! function_exists('track_activity')) {
    /**
     * Track user activity with optimized performance.
     *
     * @param  array  $previousUpdateData  Row state BEFORE the change
     * @param  mixed  $model               Eloquent model instance (optional context)
     * @param  array  $data                Changed fields AFTER the change
     * @param  mixed  $id                  Affected record id
     * @param  string  $table_name         Affected table
     * @param  int|string  $method         1=UPDATE 2=ACTIVATE 3=DEACTIVATE 4=DELETE (or action string)
     * @return bool
     */
    function track_activity($previousUpdateData, $model, $data, $id, $table_name, $method)
    {
        try {
            if (empty($previousUpdateData) || empty($data)) {
                return false;
            }

            if (! is_array($previousUpdateData)) {
                $previousUpdateData = (array) $previousUpdateData;
            }

            if (! is_array($data)) {
                $data = (array) $data;
            }

            // Compare new values against old values to detect real changes.
            $resultDiffUpdate = array_diff_assoc($data, $previousUpdateData);

            if (empty($resultDiffUpdate)) {
                return true;
            }

            $userRole = session('role', 'Unknown');
            $userName = session('name', 'Unknown');
            $ip = request()->ip();

            $log_text = $userRole.', '.$userName.' updated '.$table_name.' #'.$id.' from '.$ip;

            return activity_log_update($method, $log_text, $table_name, $previousUpdateData, $data, ['id' => $id]);
        } catch (\Throwable $e) {
            Log::error('Error in track_activity: '.$e->getMessage(), [
                'table' => $table_name,
                'id' => $id,
                'method' => $method,
            ]);

            return false;
        }
    }
}

if (! function_exists('activity_log_update')) {
    /**
     * Persist an activity record using the ActivityLog model.
     *
     * @param  int|string  $method
     * @param  string  $log_text
     * @param  string  $table_name
     * @param  array  $trans_cmsOldData
     * @param  array  $update_cms_data
     * @param  array  $update_where_to_array
     * @return bool
     */
    function activity_log_update($method, $log_text, $table_name, $trans_cmsOldData, $update_cms_data, $update_where_to_array)
    {
        try {
            $actionMap = [
                1 => 'UPDATE',
                2 => 'ACTIVATE',
                3 => 'DEACTIVATE',
                4 => 'DELETE',
            ];

            $actionType = $actionMap[$method] ?? (is_string($method) && $method !== '' ? strtoupper($method) : 'UPDATE');

            $recordId = is_array($update_where_to_array) ? ($update_where_to_array['id'] ?? null) : null;

            ActivityLog::record([
                'action_type' => $actionType,
                'table_name' => $table_name,
                'record_id' => is_numeric($recordId) ? (int) $recordId : null,
                'log_text' => $log_text,
                'old_data' => $trans_cmsOldData,
                'updated_data' => $update_cms_data,
                'severity' => $actionType === 'DELETE' ? 'warning' : 'info',
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Error in activity_log_update: '.$e->getMessage(), [
                'table' => $table_name,
                'method' => $method,
                'log_text' => $log_text,
            ]);

            return false;
        }
    }
}

if (! function_exists('get_user_activity_count')) {
    /**
     * Get cached user activity count for performance.
     *
     * @param  int  $user_id
     * @param  string|null  $table_name
     * @return int
     */
    function get_user_activity_count($user_id, $table_name = null)
    {
        $cacheKey = "user_activity_count_{$user_id}_".($table_name ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($user_id, $table_name) {
            $query = ActivityLog::where('user_id', $user_id);

            if ($table_name) {
                $query->where('table_name', $table_name);
            }

            return $query->count();
        });
    }
}

if (! function_exists('clear_activity_cache')) {
    /**
     * Clear activity-related cache entries for a user.
     * NOTE: never flushes the entire cache store.
     *
     * @param  int|null  $user_id
     * @param  string|null  $table_name
     * @return void
     */
    function clear_activity_cache($user_id = null, $table_name = null)
    {
        if (! $user_id) {
            return;
        }

        Cache::forget("user_activity_count_{$user_id}_".($table_name ?? 'all'));
        Cache::forget("user_activity_count_{$user_id}_all");
    }
}
