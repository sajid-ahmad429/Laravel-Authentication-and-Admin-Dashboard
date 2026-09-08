<?php

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

if (! function_exists('track_activity')) {
    /**
     * Record an audit trail entry for a data mutation.
     *
     * @param  array|null $previousData Old attribute values
     * @param  mixed      $model        Affected model (optional)
     * @param  array      $data         New attribute values
     * @param  mixed      $id           Affected record id
     * @param  string     $tableName    Affected table
     * @param  int        $method       1=create, 2=update, 3=trash, 4=delete
     */
    function track_activity(?array $previousData, $model, array $data, $id, string $tableName, int $method): bool
    {
        try {
            if (empty($data)) {
                return false;
            }

            $methodMap = [
                1 => 'CREATE',
                2 => 'UPDATE',
                3 => 'TRASH',
                4 => 'DELETE',
            ];

            $actionType = $methodMap[$method] ?? 'UPDATE';

            $sessionName  = session('name');
            $sessionEmail = session('email');

            ActivityLog::create([
                'user_id'      => session('id'),
                'user_name'    => $sessionName ?? 'System',
                'user_email'   => $sessionEmail,
                'method'       => request()->method(),
                'action_type'  => $actionType,
                'table_name'   => $tableName,
                'record_id'    => is_numeric($id) ? (int) $id : null,
                'log_text'     => sprintf(
                    '%s (%s) performed %s on %s record #%s',
                    $sessionName ?? 'System',
                    session('role') ?? 'system',
                    strtolower($actionType),
                    $tableName,
                    (string) $id
                ),
                'route_url'    => request()->fullUrl(),
                'ip_address'   => request()->ip(),
                'user_agent'   => (string) request()->userAgent(),
                'old_data'     => $previousData,
                'updated_data' => $data,
                'severity'     => in_array($method, [3, 4], true) ? 'warning' : 'info',
                'logged_at'    => now(),
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('track_activity failed: '.$e->getMessage(), [
                'table' => $tableName, 'id' => $id, 'method' => $method,
            ]);

            return false;
        }
    }
}

if (! function_exists('activity_log_update')) {
    /**
     * Backwards compatible alias for track_activity().
     */
    function activity_log_update($method, $logText, $tableName, $oldData, $newData, $where): bool
    {
        return track_activity(is_array($oldData) ? $oldData : null, null, is_array($newData) ? $newData : [], $where['id'] ?? null, (string) $tableName, (int) $method);
    }
}
