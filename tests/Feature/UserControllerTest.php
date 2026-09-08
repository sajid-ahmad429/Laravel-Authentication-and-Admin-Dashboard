<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_index_page_loads_and_calculates_summary_counts(): void
    {
        User::factory()->create(['status' => 1, 'trash' => 0]);
        User::factory()->create(['status' => 1, 'trash' => 0]);
        User::factory()->create(['status' => 0, 'trash' => 0]);

        $response = $this->withSession(['isLoggedIn' => true])
            ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertViewHas('active', 2);
        $response->assertViewHas('inactive', 1);
        $response->assertViewHas('totalUsers', 3);

        $this->assertTrue(Cache::has('user_counts_summary'));
    }

    public function test_user_table_data_endpoint_returns_json_with_cached_aggregates(): void
    {
        User::factory()->create(['status' => 1, 'trash' => 0, 'name' => 'Alice']);
        User::factory()->create(['status' => 0, 'trash' => 0, 'name' => 'Bob']);

        $response = $this->withSession(['isLoggedIn' => true])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('admin.users.data'), [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => ''],
                'order' => [['column' => 0, 'dir' => 'desc']],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'draw' => 1,
                'recordsTotal' => 2,
                'recordsFiltered' => 2,
                'totalActiveRecods' => 1,
                'totalInActiveRecods' => 1,
                'totalTrashedRecods' => 0,
            ]);

        $this->assertTrue(Cache::has('user_dashboard_aggregates'));
    }
}
