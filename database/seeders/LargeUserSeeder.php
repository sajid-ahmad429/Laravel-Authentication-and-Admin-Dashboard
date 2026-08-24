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
        $countries = ['India']; // Focus on India region

        // Properly categorized Indian names (Hindu and Muslim)
        $hinduFirstNames = ['Aarav', 'Vivaan', 'Aditya', 'Vihaan', 'Arjun', 'Sai', 'Reyansh', 'Ayaan', 'Krishna', 'Ishaan', 'Diya', 'Ananya', 'Sanya', 'Priya', 'Neha', 'Pooja', 'Rohan', 'Amit', 'Rahul', 'Sneha'];
        $hinduLastNames = ['Sharma', 'Verma', 'Gupta', 'Malhotra', 'Sharma', 'Patel', 'Reddy', 'Iyer', 'Kumar', 'Singh', 'Joshi', 'Mehta'];

        $muslimFirstNames = ['Mohammed', 'Ahmed', 'Ali', 'Ibrahim', 'Hassan', 'Zayd', 'Hamza', 'Bilal', 'Omar', 'Usman', 'Ayesha', 'Fatima', 'Zainab', 'Maryam', 'Khadija', 'Aaliyah', 'Sana', 'Yasmin', 'Tariq', 'Imran'];
        $muslimLastNames = ['Khan', 'Ali', 'Shaikh', 'Siddiqui', 'Pathan', 'Ansari', 'Malik', 'Mirza', 'Chaudhry', 'Qureshi', 'Syed', 'Farooqui'];

        $batch = [];
        $totalUsers = 1000;
        $batchSize = 250;

        for ($i = 1; $i <= $totalUsers; $i++) {
            // Randomly choose between Hindu and Muslim community pool to keep it balanced
            $isHindu = (rand(0, 1) == 1);

            if ($isHindu) {
                $name = $hinduFirstNames[array_rand($hinduFirstNames)] . ' ' . $hinduLastNames[array_rand($hinduLastNames)];
            } else {
                $name = $muslimFirstNames[array_rand($muslimFirstNames)] . ' ' . $muslimLastNames[array_rand($muslimLastNames)];
            }

            $batch[] = [
                'name' => $name,
                'email' => "user{$i}_" . Str::random(5) . "@example.com",
                'contact_no' => '+91 ' . rand(7000000000, 9999999999), // Indian format mobile numbers
                'company_name' => fake()->company(),
                'country' => 'India',
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