<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Bulk demo data seeder.
 *
 * Creates a realistic volume of accounts (for testing server-side tables at
 * scale) and assigns Spatie roles in bulk via the pivot tables.
 */
class LargeUserSeeder extends Seeder
{
    public function run(): void
    {
        $rolesList = ['author', 'maintainer', 'editor', 'subscriber'];
        $plansList = ['basic', 'professional', 'enterprise'];
        $countries = ['United States', 'United Kingdom', 'Canada', 'Australia', 'India', 'Germany', 'France'];

        $hashedPassword = Hash::make(env('SEED_DEMO_PASSWORD', 'Demo@12345'));
        $totalUsers = (int) env('SEED_DEMO_USERS', 1000);
        $batchSize = 250;
        $createdIds = [];
        $batch = [];

        for ($i = 1; $i <= $totalUsers; $i++) {
            $batch[] = [
                'name'              => fake()->name(),
                'email'             => "user{$i}_".Str::random(5).'@example.com',
                'contact_no'        => fake()->phoneNumber(),
                'company_name'      => fake()->company(),
                'country'           => $countries[array_rand($countries)],
                'plan'              => $plansList[array_rand($plansList)],
                'email_verified_at' => now(),
                'password'          => $hashedPassword,
                'status'            => rand(0, 1),
                'activated'         => 1,
                'trash'             => rand(1, 100) > 95 ? 1 : 0, // ~5% trashed to demo the recycle bin
                'remember_token'    => Str::random(10),
                'created_at'        => now()->subDays(rand(0, 13)),
                'updated_at'        => now(),
            ];

            if (count($batch) >= $batchSize) {
                $createdIds = array_merge($createdIds, $this->insertBatch($batch, $rolesList));
                $batch = [];
            }
        }

        if (! empty($batch)) {
            $createdIds = array_merge($createdIds, $this->insertBatch($batch, $rolesList));
        }

        $this->command?->info("Seeded {$totalUsers} demo users with Spatie roles.");

        // Invalidate aggregate caches (single source of truth keys).
        foreach (User::statCacheKeys() as $key) {
            cache()->forget($key);
        }
        cache()->forget('dashboard_trend_14d');
        cache()->forget('dashboard_role_breakdown');
    }

    /**
     * Insert a batch and assign each new user a random Spatie role.
     *
     * @return array<int, int>
     */
    protected function insertBatch(array $batch, array $rolesList): array
    {
        $emails = array_column($batch, 'email');

        User::insert($batch);

        // Fetch back exactly the users we just inserted (unique emails).
        $ids = User::query()
            ->whereIn('email', $emails)
            ->whereDoesntHave('roles')
            ->pluck('id')
            ->all();

        $pivots = [];

        foreach ($ids as $id) {
            $role = $rolesList[array_rand($rolesList)];
            $roleId = \Spatie\Permission\Models\Role::where('name', $role)->value('id');

            if ($roleId) {
                $pivots[] = [
                    'role_id'    => $roleId,
                    'model_type' => 'App\Models\User',
                    'model_id'   => $id,
                ];
            }
        }

        // model_has_roles has a unique(role_id, model_id, model_type) index — insertOrIgnore is safe.
        foreach (array_chunk($pivots, 250) as $chunk) {
            \Illuminate\Support\Facades\DB::table('model_has_roles')->insertOrIgnore($chunk);
        }

        return $ids;
    }
}
