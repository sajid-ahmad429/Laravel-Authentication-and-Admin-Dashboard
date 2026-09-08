<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
        'updated_at',
        'deleted_at'
    ];

    protected $hidden = [
        'password',
        'reset_token',
        'activate_token'
    ];

    // Password hashing on create & update
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->password = $model->passwordHash($model->password);
        });

        static::updating(function ($model) {
            if (isset($model->password)) {
                $model->password = $model->passwordHash($model->password);
            }
        });
    }

    protected function passwordHash($password)
    {
        if (!Hash::needsRehash($password)) {
            return $password;
        }
        return Hash::make($password);
    }

    // Verify user login credentials
    public static function verifyUser($email, $password, $roles)
    {
        $user = self::where('email', trim($email))
                    ->where('roles', $roles)
                    ->first();

        if ($user) {
            if ($user->activated != 1) {
                return 2; // Account not activated
            }

            if (Hash::check($password, $user->password)) {
                return 1; // Successful login
            }

            return 0; // Invalid password
        }

        return 0; // User not found
    }

    // Save user login session (Synced with advanced migration)
    public static function logLogin(array $data)
    {
        return DB::table('auth_logins')->insert([
            'user_id'        => $data['user_id'] ?? null,
            'name'           => $data['name'] ?? null,
            'email'          => $data['email'] ?? null,
            'role'           => $data['role'] ?? null,
            'ip_address'     => $data['ip_address'] ?? request()->ip(),
            'user_agent'     => $data['user_agent'] ?? request()->userAgent(),
            'device_type'    => $data['device_type'] ?? 'Desktop',
            'successful'     => $data['successful'] ?? false,
            'failure_reason' => $data['failure_reason'] ?? null,
            'logged_in_at'   => $data['logged_in_at'] ?? now(),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    // Get Auth Token by User ID
    public static function getAuthTokenByUserId($userID)
    {
        return DB::table('auth_tokens')->where('user_id', $userID)->first();
    }

    // Insert Auth Token (Accepts array to match Library call)
    public static function insertToken(array $data)
    {
        return DB::table('auth_tokens')->insert([
            'user_id'         => $data['user_id'] ?? null,
            'selector'        => $data['selector'] ?? null,
            'hashedvalidator' => $data['hashedvalidator'] ?? null,
            'token_type'      => $data['token_type'] ?? 'remember_me',
            'expires_at'      => $data['expires_at'] ?? null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    // Update Auth Token by User ID (Fixed to target specific user)
    public static function updateToken(array $data)
    {
        return DB::table('auth_tokens')
            ->where('user_id', $data['user_id'])
            ->update([
                'selector'        => $data['selector'],
                'hashedvalidator' => $data['hashedvalidator'],
                'expires_at'      => $data['expires_at'],
                'updated_at'      => now(),
            ]);
    }

    // Get Auth Token by Selector
    public static function getAuthTokenBySelector($selector)
    {
        return DB::table('auth_tokens')->where('selector', $selector)->first();
    }

    // Delete Auth Token by User ID
    public static function deleteTokenByUserId($userID)
    {
        return DB::table('auth_tokens')->where('user_id', $userID)->delete();
    }

    // Verify email existence
    public static function verifyEmail($email)
    {
        $user = self::where('email', $email)->first();
        return $user ? $user : false;
    }

    // Update the updated_at timestamp
    public static function updatedAt($id)
    {
        $affectedRows = self::where('id', $id)->update(['updated_at' => now()]);
        return $affectedRows === 1;
    }
}