<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The legacy string `roles` column on `users` shadowed the Spatie
 * `roles()` relation, silently breaking hasRole()/getRoleNames() and
 * therefore every authorization check that depends on them.
 *
 * This migration:
 *  1. Promotes each user's legacy column value into the Spatie tables.
 *  2. Drops the column (and its index) so the relation works normally.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'roles')) {
            $guard = config('auth.defaults.guard', 'web');

            $users = DB::table('users')->select(['id', 'roles'])->whereNotNull('roles')->get();

            foreach ($users as $user) {
                $roleName = strtolower(trim((string) $user->roles));

                if ($roleName === '') {
                    continue;
                }

                $exists = DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', 'App\Models\User')
                    ->exists();

                if (! $exists) {
                    try {
                        $userModel = \App\Models\User::find($user->id);
                        $userModel?->assignRole($roleName);
                    } catch (Throwable $e) {
                        // Role name not defined yet — seeders create the base roles.
                        report($e);
                    }
                }
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('roles');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'roles')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('roles')->nullable()->after('plan');
            });

            // Restore the primary role name per user where possible.
            $users = DB::table('users')->select('id')->get();
            foreach ($users as $user) {
                $userModel = \App\Models\User::find($user->id);
                $role = $userModel?->getRoleNames()->first();
                if ($role) {
                    DB::table('users')->where('id', $user->id)->update(['roles' => strtolower($role)]);
                }
            }
        }
    }
};
