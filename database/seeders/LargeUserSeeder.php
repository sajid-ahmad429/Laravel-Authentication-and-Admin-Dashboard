<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LargeUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Temporarily disable activity logging events during mass seeding for speed
        User::flushEventListeners();

        $hashedPassword = Hash::make('password123');
        $rolesList = ['author', 'maintainer', 'editor', 'subscriber'];
        $plansList = ['Basic', 'Professional', 'Enterprise'];
        $countries = ['United States', 'United Kingdom', 'Canada', 'Australia', 'India', 'Germany', 'France'];

        $batch = [];
        $totalUsers = 1000;
        $batchSize = 250;

        for ($i = 1; $i <= $totalUsers; $i++) {
            $batch[] = [
                'name' => fake()->name(),
                'email' => "user{$i}_" . Str::random(5) . "@example.com",
                'contact_no' => fake()->phoneNumber(),
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
                User::insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            User::insert($batch);
        }

        // Clear user count cache after bulk seeding
        cache()->forget('users_all_count');
        cache()->forget('users_inactive_count');
        cache()->forget('users_active_count');
        cache()->forget('users_list_data');
    }
}
