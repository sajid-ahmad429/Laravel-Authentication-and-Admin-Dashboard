<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure superadmin role exists
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin']);

        // SECURITY: the password comes from the environment. If it is not
        // provided a random one is generated and printed ONCE on the console
        // — no shared credentials are committed to the repository.
        $email = env('SEED_SUPERADMIN_EMAIL', 'admin@example.com');
        $password = env('SEED_SUPERADMIN_PASSWORD') ?: Str::password(20, symbols: true);

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'      => env('SEED_SUPERADMIN_NAME', 'Super Admin'),
                'password'  => $password,
                'activated' => 1,
                'status'    => 1,
                'trash'     => 0,
            ]
        );

        // Sync Spatie superadmin role
        $user->syncRoles([$superadminRole]);

        if (! env('SEED_SUPERADMIN_PASSWORD')) {
            $this->command?->warn("Super Admin created: {$email}");
            $this->command?->warn("Generated password (shown once): {$password}");
        }
    }
}
