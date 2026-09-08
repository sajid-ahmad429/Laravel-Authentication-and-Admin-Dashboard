<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

if (!function_exists('track_activity')) {
    /**
     * Track user activity with optimized performance
     * 
     * @param array $previousUpdateData
     * @param object $model
     * @param array $data
     * @param mixed $id
     * @param string $table_name
     * @param int $method
     * @return bool
     */
    function track_activity($previousUpdateData, $model, $data, $id, $table_name, $method)
    {
        try {
            // Early return if no changes detected
            if (empty($previousUpdateData) || empty($data)) {
                return false;
            }

            // Optimize array comparison
            $resultDiffUpdate = array_diff_assoc($previousUpdateData, $data);

            if (!empty($resultDiffUpdate)) {
                // Prepare optimized activity data
                $trans_OldData = $previousUpdateData;
                $update_data = $data;
                $update_where_to_array = ['id' => $id];

                // Get session data efficiently
                $sessionData = session()->only(['roles', 'name', 'id', 'firstname', 'lastname']);
                $userRole = $sessionData['roles'] ?? 'Unknown';
                $userName = $sessionData['name'] ?? 'Unknown';
                $ip = request()->ip();
                
                $log_text = $userRole . ", " . $userName . ' updated cms data from ' . $ip;

                // Method = 1 for update
                return activity_log_update($method, $log_text, $table_name, $trans_OldData, $update_data, $update_where_to_array);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error in track_activity: ' . $e->getMessage(), [
                'table' => $table_name,
                'id' => $id,
                'method' => $method
            ]);
            return false;
        }
    }
}

if (!function_exists('activity_log_update')) {
    /**
     * Log activity updates with optimized performance and error handling
     * 
     * @param int $method
     * @param string $log_text
     * @param string $table_name
     * @param array $trans_cmsOldData
     * @param array $update_cms_data
     * @param array $update_where_to_array
     * @return bool
     */
    function activity_log_update($method, $log_text, $table_name, $trans_cmsOldData, $update_cms_data, $update_where_to_array)
    {
        try {
            // Get the IP address
            $ip = request()->ip();

            // Get session data efficiently
            $sessionData = session()->only(['id', 'firstname', 'lastname']);
            
            // Prepare the optimized log data array
            $log_data = [
                'method' => (int) $method,
                'tableName' => $table_name,
                'logText' => $log_text,
                'address' => $ip,
                'user_id' => $sessionData['id'] ?? null,
                'user_name' => trim(($sessionData['firstname'] ?? '') . " " . ($sessionData['lastname'] ?? '')),
                'timestamp' => now(),
                'old_data' => json_encode($trans_cmsOldData, JSON_UNESCAPED_UNICODE),
                'updated_data' => json_encode($update_cms_data, JSON_UNESCAPED_UNICODE),
                'where_to' => json_encode($update_where_to_array, JSON_UNESCAPED_UNICODE),
            ];

            // Use queue for heavy logging operations in production
            if (app()->environment('production')) {
                // Queue the logging operation for better performance
                dispatch(function () use ($log_data) {
                    \App\Models\ActivityLog::create($log_data);
                })->onQueue('logging');
            } else {
                // Direct insert for development
                $result = \App\Models\ActivityLog::create($log_data);
                return $result !== null;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error in activity_log_update: ' . $e->getMessage(), [
                'table' => $table_name,
                'method' => $method,
                'log_text' => $log_text
            ]);
            return false;
        }
    }
}

if (!function_exists('get_user_activity_count')) {
    /**
     * Get cached user activity count for performance
     * 
     * @param int $user_id
     * @param string $table_name
     * @return int
     */
    function get_user_activity_count($user_id, $table_name = null)
    {
        $cacheKey = "user_activity_count_{$user_id}_{$table_name}";
        
        return Cache::remember($cacheKey, 300, function () use ($user_id, $table_name) {
            $query = \App\Models\ActivityLog::where('user_id', $user_id);
            
            if ($table_name) {
                $query->where('tableName', $table_name);
            }
            
            return $query->count();
        });
    }
}

if (!function_exists('clear_activity_cache')) {
    /**
     * Clear activity-related cache entries
     * 
     * @param int $user_id
     * @return void
     */
    function clear_activity_cache($user_id = null)
    {
        if ($user_id) {
            Cache::forget("user_activity_count_{$user_id}_");
        } else {
            Cache::flush(); // Use with caution
        }
    }
}
