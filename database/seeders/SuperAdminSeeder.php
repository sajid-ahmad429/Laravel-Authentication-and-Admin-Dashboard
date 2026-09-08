<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

        // Create or update Super Admin user
        $user = User::updateOrCreate(
            ['email' => 'sajidahmad.9005@gmail.com'],
            [
                'name' => 'Sajid Ahmad',
                'password' => Hash::make('Smart@#2026'),
                'activated' => 1,
                'status' => 1,
                'trash' => 0,
            ]
        );

        // Sync Spatie superadmin role
        $user->syncRoles([$superadminRole]);
    }
}
