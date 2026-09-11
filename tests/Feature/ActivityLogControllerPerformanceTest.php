<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ActivityLogControllerPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_logs_data_caches_total_records_count(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 15; $i++) {
            ActivityLog::record([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'method' => 'POST',
                'action_type' => 'UPDATE',
                'table_name' => 'users',
                'log_text' => "Activity Log #{$i}",
                'ip_address' => '127.0.0.1',
            ]);
        }

        Cache::forget('dt_activity_logs_total_count');

        $expectedCount = ActivityLog::count();

        $url = '/admin/activity-logs/data?' . http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]);

        // First call should calculate and cache the total count
        $response1 = $this->getJson($url, [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response1->assertStatus(200)
            ->assertJson([
                'recordsTotal' => $expectedCount,
                'recordsFiltered' => $expectedCount,
            ]);

        $this->assertTrue(Cache::has('dt_activity_logs_total_count'));

        // Enable query log to verify that subsequent requests do not execute duplicate COUNT(*) queries
        DB::enableQueryLog();
        DB::flushQueryLog();

        $response2 = $this->getJson($url, [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response2->assertStatus(200);

        $queries = DB::getQueryLog();
        $hasTotalCountQuery = false;
        foreach ($queries as $q) {
            if (str_contains($q['query'], 'count(') && !str_contains($q['query'], 'where')) {
                $hasTotalCountQuery = true;
                break;
            }
        }

        $this->assertFalse($hasTotalCountQuery, 'Total records count query should have been retrieved from cache.');
    }
}
