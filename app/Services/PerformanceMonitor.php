<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceMonitor
{
    protected $metrics = [];
    protected $startTime;
    protected $memoryStart;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->memoryStart = memory_get_usage(true);
    }

    /**
     * Start monitoring a specific operation
     */
    public function startTimer(string $operation): void
    {
        $this->metrics[$operation] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
        ];
    }

    /**
     * End monitoring and record metrics
     */
    public function endTimer(string $operation): array
    {
        if (!isset($this->metrics[$operation])) {
            return [];
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $this->metrics[$operation]['end_time'] = $endTime;
        $this->metrics[$operation]['end_memory'] = $endMemory;
        $this->metrics[$operation]['duration'] = $endTime - $this->metrics[$operation]['start_time'];
        $this->metrics[$operation]['memory_used'] = $endMemory - $this->metrics[$operation]['start_memory'];

        return $this->metrics[$operation];
    }

    /**
     * Get database performance metrics
     */
    public function getDatabaseMetrics(): array
    {
        $metrics = [];
        
        try {
            // Get slow query log if available
            $slowQueries = DB::select("SHOW GLOBAL STATUS LIKE 'Slow_queries'");
            $metrics['slow_queries'] = $slowQueries[0]->Value ?? 0;

            // Get connection info
            $metrics['connections'] = DB::select("SHOW GLOBAL STATUS LIKE 'Connections'")[0]->Value ?? 0;
            $metrics['threads_connected'] = DB::select("SHOW GLOBAL STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;

        } catch (\Exception $e) {
            Log::warning('Could not retrieve database metrics: ' . $e->getMessage());
            $metrics['error'] = 'Database metrics unavailable';
        }

        return $metrics;
    }

    /**
     * Get cache performance metrics
     */
    public function getCacheMetrics(): array
    {
        $metrics = [];
        
        try {
            // Test cache performance
            $cacheKey = 'performance_test_' . time();
            $testData = 'performance_test_data';
            
            $startTime = microtime(true);
            Cache::put($cacheKey, $testData, 60);
            $writeTime = microtime(true) - $startTime;
            
            $startTime = microtime(true);
            $retrievedData = Cache::get($cacheKey);
            $readTime = microtime(true) - $startTime;
            
            Cache::forget($cacheKey);
            
            $metrics['cache_write_time'] = $writeTime;
            $metrics['cache_read_time'] = $readTime;
            $metrics['cache_working'] = $retrievedData === $testData;
            
        } catch (\Exception $e) {
            Log::warning('Could not test cache performance: ' . $e->getMessage());
            $metrics['error'] = 'Cache metrics unavailable';
        }

        return $metrics;
    }

    /**
     * Get system performance metrics
     */
    public function getSystemMetrics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'opcache_enabled' => function_exists('opcache_get_status') ? opcache_get_status()['opcache_enabled'] : false,
            'current_memory_usage' => memory_get_usage(true),
            'peak_memory_usage' => memory_get_peak_usage(true),
            'total_execution_time' => microtime(true) - $this->startTime,
        ];
    }

    /**
     * Get all performance metrics
     */
    public function getAllMetrics(): array
    {
        return [
            'system' => $this->getSystemMetrics(),
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
            'operations' => $this->metrics,
        ];
    }

    /**
     * Log performance metrics
     */
    public function logMetrics(string $context = 'general'): void
    {
        $metrics = $this->getAllMetrics();
        
        Log::info("Performance Metrics - {$context}", $metrics);
        
        // Store in cache for monitoring dashboard
        Cache::put("performance_metrics_{$context}_" . date('Y-m-d-H'), $metrics, 3600);
    }

    /**
     * Check if performance is within acceptable limits
     */
    public function checkPerformanceHealth(): array
    {
        $metrics = $this->getSystemMetrics();
        $issues = [];

        // Check memory usage
        $memoryUsage = $metrics['current_memory_usage'];
        $memoryLimit = $this->parseMemoryLimit($metrics['memory_limit']);
        
        if ($memoryUsage > ($memoryLimit * 0.8)) {
            $issues[] = 'High memory usage: ' . round(($memoryUsage / $memoryLimit) * 100, 2) . '%';
        }

        // Check execution time
        if ($metrics['total_execution_time'] > 5) {
            $issues[] = 'Long execution time: ' . round($metrics['total_execution_time'], 2) . 's';
        }

        // Check OPcache
        if (!$metrics['opcache_enabled']) {
            $issues[] = 'OPcache is not enabled';
        }

        return [
            'healthy' => empty($issues),
            'issues' => $issues,
            'metrics' => $metrics
        ];
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = (int) $memoryLimit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}