<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $table = 'users';

    /**
     * Mass assignment is restricted to safe, user-editable columns.
     * Security columns (activated, status, trash, tokens) are only ever
     * written through explicit forceFill() calls in reviewed code paths.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'contact_no',
        'company_name',
        'country',
        'plan',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'reset_token',
        'activate_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'reset_expire'      => 'datetime',
            'activate_expire'   => 'datetime',
            'status'            => 'integer',
            'trash'             => 'integer',
            'activated'         => 'integer',
        ];
    }

    /* ==================================================================
     |  Scopes
     * ================================================================== */

    /** Non-trashed users. */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->where('trash', 0);
    }

    /** Non-trashed + active status. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1)->where('trash', 0);
    }

    /** Non-trashed + inactive status. */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 0)->where('trash', 0);
    }

    /* ==================================================================
     |  Helpers
     * ================================================================== */

    /**
     * Primary Spatie role name (lowercase), or null when none assigned.
     */
    public function roleName(): ?string
    {
        return strtolower($this->getRoleNames()->first() ?? '');
    }

    /**
     * Numeric rank of the user's primary role.
     */
    public function roleRank(): int
    {
        return (int) config("auth.role_rank.{$this->roleName()}", 0);
    }

    /**
     * Is this a system-protected role account (e.g. Super Admin)?
     */
    public function isProtected(): bool
    {
        return in_array($this->roleName(), (array) config('auth.protected_roles', ['superadmin']), true);
    }

    /**
     * Avatar URL with graceful fallback to UI initials rendered client side.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->avatar ? asset($this->avatar) : null,
        );
    }

    /* ==================================================================
     |  Cache
     * ================================================================== */

    /**
     * Central cache keys for aggregate counters — the ONLY place that knows
     * the key names, so invalidation can never drift again.
     */
    public static function statCacheKeys(): array
    {
        return ['users_stats_v2'];
    }

    public static function flushStatCache(): void
    {
        foreach (self::statCacheKeys() as $key) {
            cache()->forget($key);
        }
    }

    /**
     * Cached dashboard aggregate (single query, 5 min TTL).
     */
    public static function cachedStats(): array
    {
        return cache()->remember('users_stats_v2', 300, function () {
            $row = self::query()
                ->selectRaw("COUNT(CASE WHEN trash = 0 THEN 1 END) AS total_count")
                ->selectRaw("COUNT(CASE WHEN trash = 0 AND status = 1 THEN 1 END) AS active_count")
                ->selectRaw("COUNT(CASE WHEN trash = 0 AND status = 0 THEN 1 END) AS inactive_count")
                ->selectRaw("COUNT(CASE WHEN trash = 1 THEN 1 END) AS trashed_count")
                ->selectRaw("COUNT(CASE WHEN trash = 0 AND activated = 0 THEN 1 END) AS unactivated_count")
                ->first();

            return [
                'total'       => (int) ($row->total_count ?? 0),
                'active'      => (int) ($row->active_count ?? 0),
                'inactive'    => (int) ($row->inactive_count ?? 0),
                'trashed'     => (int) ($row->trashed_count ?? 0),
                'unactivated' => (int) ($row->unactivated_count ?? 0),
            ];
        });
    }

    protected static function booted(): void
    {
        // Any create/update/delete invalidates the aggregate cache.
        static::saved(fn () => self::flushStatCache());
        static::deleted(fn () => self::flushStatCache());
    }
}
