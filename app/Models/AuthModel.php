<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Legacy compatibility model for the `users` table covering authentication
 * side-table writes (login audit trail + remember-me tokens).
 *
 * NOTE: password changes must go through \App\Models\User which hashes via
 * the `hashed` cast. This model intentionally does NOT hash to avoid the
 * historical double-hashing bug (two models hashing the same column).
 */
class AuthModel extends Model
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'reset_token',
        'reset_expire',
        'activated',
        'activate_token',
        'activate_expire',
        'roles',
    ];

    protected $hidden = [
        'password',
        'reset_token',
        'activate_token',
    ];

    // ------------------------------------------------------------------
    // Login audit trail
    // ------------------------------------------------------------------

    /**
     * Persist a login (success or failure) record.
     */
    public static function logLogin(array $data): bool
    {
        return (bool) DB::table('auth_logins')->insert([
            'user_id'        => $data['user_id'] ?? null,
            'name'           => $data['name'] ?? null,
            'email'          => $data['email'] ?? null,
            'role'           => $data['role'] ?? null,
            'ip_address'     => $data['ip_address'] ?? request()->ip(),
            'user_agent'     => $data['user_agent'] ?? request()->userAgent(),
            'device_type'    => $data['device_type'] ?? 'Desktop',
            'successful'     => (bool) ($data['successful'] ?? false),
            'failure_reason' => $data['failure_reason'] ?? null,
            'logged_in_at'   => $data['logged_in_at'] ?? now(),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    // ------------------------------------------------------------------
    // Remember-me tokens
    // ------------------------------------------------------------------

    public static function getAuthTokenByUserId(int $userID): ?object
    {
        return DB::table('auth_tokens')->where('user_id', $userID)->first();
    }

    public static function insertToken(array $data): bool
    {
        return (bool) DB::table('auth_tokens')->insert([
            'user_id'         => $data['user_id'],
            'selector'        => $data['selector'],
            'hashedvalidator' => $data['hashedvalidator'],
            'token_type'      => $data['token_type'] ?? 'remember_me',
            'expires_at'      => $data['expires_at'] ?? $data['expires'] ?? now()->addDays(30),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    public static function updateToken(array $data): int
    {
        return DB::table('auth_tokens')
            ->where('user_id', $data['user_id'])
            ->update([
                'selector'        => $data['selector'],
                'hashedvalidator' => $data['hashedvalidator'],
                'expires_at'      => $data['expires_at'] ?? $data['expires'] ?? now()->addDays(30),
                'updated_at'      => now(),
            ]);
    }

    public static function getAuthTokenBySelector(string $selector): ?object
    {
        return DB::table('auth_tokens')->where('selector', $selector)->first();
    }

    public static function deleteTokenByUserId(int $userID): int
    {
        return DB::table('auth_tokens')->where('user_id', $userID)->delete();
    }

    /**
     * Prune expired remember-me tokens (call from a scheduled job).
     */
    public static function pruneExpiredTokens(): int
    {
        return DB::table('auth_tokens')->where('expires_at', '<', now())->delete();
    }
}
