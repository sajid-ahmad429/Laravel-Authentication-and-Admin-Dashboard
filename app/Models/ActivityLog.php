<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activitymaster';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'method',
        'action_type',
        'table_name',
        'record_id',
        'log_text',
        'route_url',
        'ip_address',
        'user_agent',
        'device_type',
        'location_country',
        'old_data',
        'updated_data',
        'additional_meta',
        'severity',
        'logged_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_data' => 'array',
        'updated_data' => 'array',
        'additional_meta' => 'array',
        'logged_at' => 'datetime',
    ];

    public $timestamps = true;

    /**
     * Get the user that performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper method to easily log any activity globally from anywhere.
     * 
     * @param array $data
     * @return ActivityLog
     */
    public static function record(array $data)
    {
        $currentUser = Auth::user();

        return self::create([
            'user_id'          => $data['user_id'] ?? Auth::id(),
            'user_name'        => $data['user_name'] ?? ($currentUser ? $currentUser->name : null),
            'user_email'       => $data['user_email'] ?? ($currentUser ? $currentUser->email : null),
            'method'           => $data['method'] ?? request()->method(),
            'action_type'      => $data['action_type'] ?? 'INFO',
            'table_name'       => $data['table_name'] ?? null,
            'record_id'        => $data['record_id'] ?? null,
            'log_text'         => $data['log_text'] ?? 'System activity performed.',
            'route_url'        => $data['route_url'] ?? request()->fullUrl(),
            'ip_address'       => $data['ip_address'] ?? request()->ip(),
            'user_agent'       => $data['user_agent'] ?? request()->userAgent(),
            'device_type'      => $data['device_type'] ?? 'Desktop',
            'location_country' => $data['location_country'] ?? null,
            'old_data'         => $data['old_data'] ?? null,
            'updated_data'     => $data['updated_data'] ?? null,
            'additional_meta'  => $data['additional_meta'] ?? null,
            'severity'         => $data['severity'] ?? 'info',
            'logged_at'        => now(),
        ]);
    }
}