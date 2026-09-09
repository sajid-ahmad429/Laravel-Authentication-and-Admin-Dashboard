<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserControllerPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_table_data_caches_aggregate_counts(): void
    {
        // Seed test users
        User::factory()->count(10)->create(['status' => 1, 'trash' => 0]);
        User::factory()->count(5)->create(['status' => 0, 'trash' => 0]);

        Cache::forget('users_aggregate_counts');

        $payload = [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ];

        // First call should populate the cache
        $response1 = $this->getJson('/admin/users/registry-data?' . http_build_query($payload), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response1->assertStatus(200)
            ->assertJson([
                'totalActiveRecods' => 10,
                'totalInActiveRecods' => 5,
            ]);

        $this->assertTrue(Cache::has('users_aggregate_counts'));

        // Query count check for repeated calls using cached aggregate counts
        DB::enableQueryLog();
        DB::flushQueryLog();

        $response2 = $this->getJson('/admin/users/registry-data?' . http_build_query($payload), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response2->assertStatus(200);

        $queries = DB::getQueryLog();
        // Confirm no raw selectRaw aggregate query was run on subsequent call
        $hasAggregateQuery = false;
        foreach ($queries as $q) {
            if (str_contains($q['query'], 'COUNT(CASE WHEN status = 1')) {
                $hasAggregateQuery = true;
                break;
            }
        }

        $this->assertFalse($hasAggregateQuery, 'Aggregate query should have been skipped via cache.');
    }

    public function test_clear_user_cache_invalidates_aggregate_counts(): void
    {
        User::factory()->count(5)->create(['status' => 1, 'trash' => 0]);

        Cache::put('users_aggregate_counts', (object)['active_count' => 5, 'inactive_count' => 0, 'trashed_count' => 0], 120);

        $user = User::first();
        $encodedId = base64_encode($user->id);

        $response = $this->postJson('/admin/users/trash-toggle', [
            'id' => $encodedId,
            'action_type' => 1,
        ]);

        $response->assertStatus(200);
        $this->assertFalse(Cache::has('users_aggregate_counts'), 'users_aggregate_counts cache should be cleared on user trash toggle.');
    }

    public function test_dashboard_renders_without_user_all(): void
    {
        User::factory()->count(20)->create();

        // Simulate session logged in
        $response = $this->withSession(['isLoggedIn' => true, 'role' => 'superadmin'])
            ->get('/superadmin');

        $response->assertStatus(200);
        $response->assertViewHas('activeMenu', 'dashboard');
    }
}
