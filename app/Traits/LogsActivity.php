<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    /**
     * Boot the trait to listen for Eloquent model events.
     */
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->recordActivity('created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            $original = array_intersect_key($model->getOriginal(), $changes);

            if (!empty($changes)) {
                $model->recordActivity('updated', $original, $changes);
            }
        });

        static::deleted(function ($model) {
            $model->recordActivity('deleted', $model->getAttributes(), null);
        });
    }

    /**
     * Helper to record activity in `activitymaster` table via ActivityLog model.
     */
    public function recordActivity(string $event, ?array $oldData = null, ?array $updatedData = null): void
    {
        try {
            $currentUser = Auth::user();
            $tableName = $this->getTable();
            $recordId = $this->getKey();
            $userRole = session('role') ?? ($currentUser ? $currentUser->roles : 'System');
            $userName = session('name') ?? ($currentUser ? $currentUser->name : 'System');
            $ip = request()->ip();

            $logText = "{$userRole}, {$userName} {$event} record #{$recordId} in {$tableName} from {$ip}";

            ActivityLog::record([
                'user_id'       => session('id') ?? Auth::id(),
                'user_name'     => $userName,
                'user_email'    => session('email') ?? ($currentUser ? $currentUser->email : null),
                'method'        => request()->method(),
                'action_type'   => strtoupper($event),
                'table_name'    => $tableName,
                'record_id'     => $recordId,
                'log_text'      => $logText,
                'old_data'      => $oldData,
                'updated_data'  => $updatedData,
                'severity'      => $event === 'deleted' ? 'warning' : 'info',
            ]);
        } catch (\Exception $e) {
            Log::error('Error inside LogsActivity trait: ' . $e->getMessage(), [
                'model' => get_class($this),
                'event' => $event,
            ]);
        }
    }
}
