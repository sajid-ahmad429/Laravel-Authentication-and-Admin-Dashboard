<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ActivityLogControllerPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_logs_data_caches_total_count(): void
    {
        // Seed test activity logs
        ActivityLog::record([
            'user_name'   => 'Admin User',
            'action_type' => 'CREATE',
            'table_name'  => 'users',
            'log_text'    => 'Created new user',
        ]);
        ActivityLog::record([
            'user_name'   => 'Editor User',
            'action_type' => 'UPDATE',
            'table_name'  => 'posts',
            'log_text'    => 'Updated post title',
        ]);

        Cache::forget('dt_total_activity_logs');

        $payload = [
            'draw'   => 1,
            'start'  => 0,
            'length' => 10,
        ];

        // First call should populate the cache and return correct counts
        $response1 = $this->getJson('/admin/activity-logs/data?' . http_build_query($payload), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response1->assertStatus(200)
            ->assertJson([
                'recordsTotal'    => 2,
                'recordsFiltered' => 2,
            ]);

        $this->assertTrue(Cache::has('dt_total_activity_logs'));
        $this->assertEquals(2, Cache::get('dt_total_activity_logs'));

        // DB Query check for repeated call: should reuse cached total count and not run count(*) query
        DB::enableQueryLog();
        DB::flushQueryLog();

        $response2 = $this->getJson('/admin/activity-logs/data?' . http_build_query($payload), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response2->assertStatus(200);

        $queries = DB::getQueryLog();
        $hasCountQuery = false;
        foreach ($queries as $q) {
            if (str_contains(strtolower($q['query']), 'count(')) {
                $hasCountQuery = true;
                break;
            }
        }

        $this->assertFalse($hasCountQuery, 'Count query should be skipped on un-filtered request when total is cached.');
    }

    public function test_get_logs_data_filters_with_search(): void
    {
        ActivityLog::record([
            'user_name'   => 'Alice',
            'action_type' => 'CREATE',
            'table_name'  => 'users',
            'log_text'    => 'Unique test record alpha',
        ]);
        ActivityLog::record([
            'user_name'   => 'Bob',
            'action_type' => 'DELETE',
            'table_name'  => 'roles',
            'log_text'    => 'Deleted role beta',
        ]);

        $payload = [
            'draw'   => 1,
            'start'  => 0,
            'length' => 10,
            'search' => ['value' => 'alpha'],
        ];

        $response = $this->getJson('/admin/activity-logs/data?' . http_build_query($payload), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'recordsTotal'    => 2,
                'recordsFiltered' => 1,
            ]);
    }
}
