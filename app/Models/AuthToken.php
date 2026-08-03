<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthToken extends Model
{
    protected $table = 'auth_tokens';

    protected $fillable = [
        'user_id',
        'selector',
        'hashedvalidator',
        'token_type',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the auth token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}