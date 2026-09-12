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

    public function test_get_logs_data_caches_total_records_count(): void
    {
        for ($i = 0; $i < 10; $i++) {
            ActivityLog::create([
                'user_name' => "User {$i}",
                'log_text' => "Sample log text {$i}",
                'action_type' => 'INFO',
                'method' => 'GET',
            ]);
        }

        Cache::forget('activity_logs_total_count');

        $payload = [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ];

        // First call populates cache
        $response1 = $this->getJson('/admin/activity-logs/data?' . http_build_query($payload), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response1->assertStatus(200)
            ->assertJson([
                'recordsTotal' => 10,
            ]);

        $this->assertTrue(Cache::has('activity_logs_total_count'));

        // Subsequent call uses cache and avoids count query
        DB::enableQueryLog();
        DB::flushQueryLog();

        $response2 = $this->getJson('/admin/activity-logs/data?' . http_build_query($payload), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response2->assertStatus(200);

        $queries = DB::getQueryLog();
        $hasCountQuery = false;
        foreach ($queries as $q) {
            if (str_contains($q['query'], 'count(*) as aggregate from "activitymaster"')) {
                $hasCountQuery = true;
                break;
            }
        }

        $this->assertFalse($hasCountQuery, 'ActivityLog total count query should be served from cache on subsequent request.');
    }

    public function test_recording_activity_log_clears_cached_count(): void
    {
        for ($i = 0; $i < 5; $i++) {
            ActivityLog::create([
                'user_name' => "User {$i}",
                'log_text' => "Sample log text {$i}",
                'action_type' => 'INFO',
                'method' => 'GET',
            ]);
        }

        Cache::put('activity_logs_total_count', 5, 120);

        ActivityLog::record([
            'log_text' => 'Test event log',
            'action_type' => 'CREATE',
        ]);

        $this->assertFalse(Cache::has('activity_logs_total_count'), 'activity_logs_total_count cache should be invalidated on ActivityLog record.');
    }
}
