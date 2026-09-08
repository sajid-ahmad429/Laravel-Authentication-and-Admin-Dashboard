<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class LargeUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Bulk-inserts demo users WITHOUT firing model events (activity logging
     * stays quiet during seeding) and then attaches the matching Spatie
     * roles in bulk so role checks behave like production data.
     */
    public function run(): void
    {
        $hashedPassword = Hash::make('password123');
        $rolesList = ['author', 'maintainer', 'editor', 'subscriber'];
        $plansList = ['Basic', 'Professional', 'Enterprise'];
        $countries = ['United States', 'United Kingdom', 'Canada', 'Australia', 'India', 'Germany', 'France'];

        $totalUsers = 1000;
        $batchSize = 250;
        $emails = [];

        $insertBatch = function (array $batch): void {
            if (! empty($batch)) {
                User::insert($batch);
            }
        };

        User::withoutEvents(function () use ($hashedPassword, $rolesList, $plansList, $countries, $totalUsers, $batchSize, &$emails, $insertBatch): void {
            $batch = [];

            for ($i = 1; $i <= $totalUsers; $i++) {
                $email = "user{$i}_".Str::random(5).'@example.com';
                $emails[] = $email;

                $batch[] = [
                    'name' => fake()->name(),
                    'email' => $email,
                    // 10-digit numbers, consistent with the app's contact validation.
                    'contact_no' => fake()->numerify('##########'),
                    'company_name' => fake()->company(),
                    'country' => $countries[array_rand($countries)],
                    'roles' => $rolesList[array_rand($rolesList)],
                    'plan' => $plansList[array_rand($plansList)],
                    'email_verified_at' => now(),
                    'password' => $hashedPassword,
                    'status' => rand(0, 1),
                    'activated' => 1,
                    'trash' => 0,
                    'remember_token' => Str::random(10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= $batchSize) {
                    $insertBatch($batch);
                    $batch = [];
                }
            }

            $insertBatch($batch);
        });

        // Attach Spatie roles in bulk (one pivot row per seeded user).
        $roleIds = Role::whereIn('name', $rolesList)->pluck('id', 'name');

        if ($roleIds->isNotEmpty()) {
            $pivotRows = [];
            User::whereIn('email', $emails)->select('id', 'roles')->chunk(500, function ($users) use ($roleIds, &$pivotRows): void {
                foreach ($users as $user) {
                    $roleName = strtolower((string) $user->roles);
                    if (! isset($roleIds[$roleName])) {
                        continue;
                    }
                    $pivotRows[] = [
                        'role_id' => $roleIds[$roleName],
                        'model_type' => User::class,
                        'model_id' => $user->id,
                    ];
                }
            });

            foreach (array_chunk($pivotRows, 500) as $chunk) {
                DB::table('model_has_roles')->insertOrIgnore($chunk);
            }

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }

        cache()->forget('users_all_count');
        cache()->forget('users_inactive_count');
        cache()->forget('users_active_count');
        cache()->forget('users_list_data');

        $this->command->info("Seeded {$totalUsers} demo users with roles.");
    }
}
