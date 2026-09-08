<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Credentials come from the environment so secrets never live in git:
     *   SUPERADMIN_EMAIL, SUPERADMIN_NAME, SUPERADMIN_PASSWORD
     * When no password is provided, a random one is generated and printed
     * once to the console (change it immediately after first login).
     */
    public function run(): void
    {
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin']);

        $email = env('SUPERADMIN_EMAIL', 'superadmin@example.com');
        $password = env('SUPERADMIN_PASSWORD');

        $generatedPassword = false;
        if (empty($password)) {
            $password = Str::random(16);
            $generatedPassword = true;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('SUPERADMIN_NAME', 'Super Admin'),
                'password' => Hash::make($password),
                'roles' => 'superadmin',
                'activated' => 1,
                'status' => 1,
                'trash' => 0,
            ]
        );

        $user->syncRoles([$superadminRole]);

        if ($generatedPassword) {
            $this->command->warn('SUPERADMIN_PASSWORD was not set — a random password was generated:');
            $this->command->line("  Email:    {$email}");
            $this->command->line("  Password: {$password}");
            $this->command->warn('Store it securely and change it after the first login.');
        } else {
            $this->command->info("Super admin ready: {$email}");
        }
    }
}
