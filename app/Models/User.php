<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;

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
        'reset_token',
        'activate_token',
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
            'reset_expire' => 'datetime',
            'activate_expire' => 'datetime',
            'status' => 'integer',
            'trash' => 'integer',
            'activated' => 'integer',
        ];
    }

    /**
     * Performance optimization: Scope for active users
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1)->where('trash', 0);
    }

    /**
     * Performance optimization: Scope for inactive users
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 0);
    }

    /**
     * Performance optimization: Scope for non-deleted users
     */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->where('trash', 0);
    }

    /**
     * Performance optimization: Scope for valid users (not status 2, not deleted)
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', '!=', 2)->where('trash', 0);
    }

    /**
     * Optimized method using scopes and caching
     */
    public function getAllCount()
    {
        return cache()->remember('users_all_count', 300, function () {
            return self::valid()->count();
        });
    }

    /**
     * Optimized method using scopes and caching
     */
    public function inactiveCount()
    {
        return cache()->remember('users_inactive_count', 300, function () {
            return self::inactive()->count();
        });
    }

    /**
     * Optimized method using scopes and caching
     */
    public function activeCount()
    {
        return cache()->remember('users_active_count', 300, function () {
            return self::active()->count();
        });
    }

    /**
     * Get user list data with optimized query
     */
    public static function getListData($columns = ['id', 'name', 'email', 'roles', 'contact_no', 'country', 'company_name', 'plan', 'status', 'activated'])
    {
        return cache()->remember('users_list_data', 300, function () use ($columns) {
            return self::select($columns)->valid()->get();
        });
    }

    /**
     * Clear user cache when user data changes
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            cache()->forget('users_all_count');
            cache()->forget('users_inactive_count');
            cache()->forget('users_active_count');
            cache()->forget('users_list_data');
        });

        static::deleted(function () {
            cache()->forget('users_all_count');
            cache()->forget('users_inactive_count');
            cache()->forget('users_active_count');
            cache()->forget('users_list_data');
        });
    }
}
