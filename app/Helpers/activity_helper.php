<?php

if (!function_exists('track_activity')) {

    function track_activity($previousUpdateData,$model, $data, $id, $table_name, $method)
    {
        $resultDiffUpdate = array_diff($previousUpdateData, $data);

        if (!empty($resultDiffUpdate)) {
            // Activity Track Starts
            $trans_OldData = $update_data = $update_where_to_array = [];
            $trans_OldData = $previousUpdateData;
            $update_data = $data;
            $update_where_to_array['id'] = $id;

            // Assuming session is already started and available
            $userRole = session('roles');
            $userName = session('name');
            $ip = request()->ip();
            $log_text = $userRole . ", " . $userName . ' updated cms data from ' . $ip;

            // Method = 1 for update
            activity_log_update($method, $log_text, $table_name, $trans_OldData, $update_data, $update_where_to_array);
        }
    }
}

if (!function_exists('activity_log_update')) {

    function activity_log_update($method, $log_text, $table_name, $trans_cmsOldData, $update_cms_data, $update_where_to_array)
    {
        // Get the IP address
        $ip = request()->ip();

        // Prepare the log data array
        $log_data = [
            'method' => $method,
            'tableName' => $table_name,
            'logText' => $log_text,
            'address' => $ip,
            'user_id' => session('id'), // Get the user ID from session
            'user_name' => session('firstname') . " " . session('lastname'), // Get the user name from session
            'timestamp' => now(), // Get the current timestamp
            'old_data' => json_encode($trans_cmsOldData), // Encode old data as JSON
            'updated_data' => json_encode($update_cms_data), // Encode updated data as JSON
            'where_to' => json_encode($update_where_to_array), // Encode where condition as JSON
        ];

        // Insert the log data into the 'activitymaster' table
        $result = \App\Models\ActivityLog::create($log_data);

        // Return the result of the insert operation
        return $result;
    }
}
