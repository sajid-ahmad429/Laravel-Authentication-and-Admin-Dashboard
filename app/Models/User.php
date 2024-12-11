<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'contact_no',
        'company_name',
        'country',
        'roles',
        'plan',
        'email_verified_at',
        'password',
        'reset_token',
        'reset_expire',
        'activated',
        'activate_token',
        'activate_expire',
        'remember_token',
        'status',
        'trash',
        'created_at',
        'updated_at',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function getAllCount()
    {
        return self::where('status', '!=', 2)
                ->where('trash', 0)
                ->count();
    }

    public function inactiveCount()
    {
        return self::where('status', 0)->count();
    }

    public function activeCount()
    {
        return self::where('trash', 0)
                ->where('status', 1)
                ->count();
    }



}
