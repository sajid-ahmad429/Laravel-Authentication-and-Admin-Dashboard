<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function index(): View
    {
        $dbStatus = true;
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = false;
        }

        $cacheStatus = true;
        try {
            Cache::put('health_check', 'ok', 10);
            $cacheStatus = Cache::get('health_check') === 'ok';
        } catch (\Exception $e) {
            $cacheStatus = false;
        }

        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        $diskFree = function_exists('disk_free_space') ? round(disk_free_space('/') / 1024 / 1024 / 1024, 2) : 'N/A';
        $diskTotal = function_exists('disk_total_space') ? round(disk_total_space('/') / 1024 / 1024 / 1024, 2) : 'N/A';

        $healthMetrics = [
            'database'     => $dbStatus,
            'cache'        => $cacheStatus,
            'pending_jobs' => $pendingJobs,
            'failed_jobs'  => $failedJobs,
            'disk_free_gb' => $diskFree,
            'disk_total_gb'=> $diskTotal,
            'php_version'  => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        $activeMenu = 'health';
        return view('admin.health.index', compact('healthMetrics', 'activeMenu'));
    }
}
