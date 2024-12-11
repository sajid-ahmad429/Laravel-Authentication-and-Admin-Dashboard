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

    // Define methods for password hashing before insert and update.
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

     /**
     * passwordHash
     *
     * @param  mixed $data
     * @return void
     */
    protected function passwordHash($password)
    {
        if (!Hash::needsRehash($password)) {
            return $password; // Return the already hashed password
        }
        return Hash::make($password); // Hash if it's not hashed yet
    }


    // Verify user login credentials
    public static function verifyUser($email, $password, $roles)
    {
        $user = self::where('email', trim($email))
                    ->where('roles', $roles)
                    ->first();

        if ($user) {
            if ($user->status != 1) {
                return 2; // Account not activated
            }

            if (Hash::check($password, $user->password)) {
                return 1; // Successful login
            }

            return 0; // Invalid password
        }

        return 0; // User not found
    }

    // Save user login session
    public function logLogin($data)
    {
        DB::table('auth_logins')->insert($data);
    }

    // Get Auth Token by User ID
    public static function getAuthTokenByUserId($userID)
    {
        return DB::table('auth_tokens')->where('user_id', $userID)->first();
    }

    // Insert Auth Token
    public static function insertToken($data)
    {
        return DB::table('auth_tokens')->insert($data);
    }

    // Update Auth Token
    public static function updateToken($data)
    {
        return DB::table('auth_tokens')->update($data);
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

    // Update Selector
    public static function updateSelector($data, $selector)
    {
        return DB::table('auth_tokens')->where('selector', $selector)->update($data);
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
