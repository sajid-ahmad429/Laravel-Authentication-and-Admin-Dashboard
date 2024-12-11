<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activitymaster'; // Assuming your table name is activitymaster
    protected $fillable = [
        'method', 'tableName', 'logText', 'address', 'user_id', 'user_name', 'timestamp', 'old_data', 'updated_data', 'where_to',
    ];

    public $timestamps = false; // Assuming you don't want to use Laravel's automatic timestamping
}
