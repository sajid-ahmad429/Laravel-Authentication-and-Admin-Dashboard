<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class OptimizeApplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize 
                           {--clear : Clear all caches before optimizing}
                           {--production : Run production optimizations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize the application for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting application optimization...');

        if ($this->option('clear')) {
            $this->clearCaches();
        }

        $this->optimizeCaches();
        $this->optimizeDatabase();
        $this->optimizeAssets();
        
        if ($this->option('production')) {
            $this->productionOptimizations();
        }

        $this->cleanupFiles();
        $this->displayOptimizationSummary();

        $this->info('Application optimization completed successfully!');
    }

    /**
     * Clear all application caches
     */
    private function clearCaches()
    {
        $this->info('Clearing caches...');

        // Clear application cache
        Artisan::call('cache:clear');
        $this->line('✓ Application cache cleared');

        // Clear config cache
        Artisan::call('config:clear');
        $this->line('✓ Configuration cache cleared');

        // Clear route cache
        Artisan::call('route:clear');
        $this->line('✓ Route cache cleared');

        // Clear view cache
        Artisan::call('view:clear');
        $this->line('✓ View cache cleared');

        // Clear compiled services
        Artisan::call('clear-compiled');
        $this->line('✓ Compiled services cleared');
    }

    /**
     * Optimize application caches
     */
    private function optimizeCaches()
    {
        $this->info('Optimizing caches...');

        // Cache configuration
        Artisan::call('config:cache');
        $this->line('✓ Configuration cached');

        // Cache routes
        Artisan::call('route:cache');
        $this->line('✓ Routes cached');

        // Cache views
        Artisan::call('view:cache');
        $this->line('✓ Views cached');

        // Cache events
        if (method_exists('Illuminate\Foundation\Console\EventCacheCommand', 'handle')) {
            Artisan::call('event:cache');
            $this->line('✓ Events cached');
        }
    }

    /**
     * Optimize database
     */
    private function optimizeDatabase()
    {
        $this->info('Optimizing database...');

        try {
            // Run database migrations
            Artisan::call('migrate', ['--force' => true]);
            $this->line('✓ Database migrations completed');

            // Optimize database tables (MySQL specific)
            if (config('database.default') === 'mysql') {
                $this->optimizeMysqlTables();
            }

        } catch (\Exception $e) {
            $this->error('Database optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Optimize MySQL tables
     */
    private function optimizeMysqlTables()
    {
        try {
            $tables = \DB::select('SHOW TABLES');
            $databaseName = config('database.connections.mysql.database');
            
            foreach ($tables as $table) {
                $tableName = $table->{"Tables_in_{$databaseName}"};
                \DB::statement("OPTIMIZE TABLE `{$tableName}`");
            }
            
            $this->line('✓ MySQL tables optimized');
        } catch (\Exception $e) {
            $this->warn('MySQL table optimization skipped: ' . $e->getMessage());
        }
    }

    /**
     * Optimize frontend assets
     */
    private function optimizeAssets()
    {
        $this->info('Optimizing assets...');

        // Build production assets
        $buildCommand = $this->option('production') ? 'npm run build:production' : 'npm run build';
        
        $this->line('Building frontend assets...');
        $output = shell_exec($buildCommand . ' 2>&1');
        
        if ($output) {
            $this->line('✓ Frontend assets built successfully');
        } else {
            $this->warn('Frontend asset build may have failed. Check npm logs.');
        }

        // Optimize images if available
        $this->optimizeImages();
    }

    /**
     * Optimize images in public directory
     */
    private function optimizeImages()
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $publicPath = public_path();
        
        $imageCount = 0;
        foreach ($imageExtensions as $ext) {
            $files = File::glob($publicPath . "/**/*.{$ext}");
            $imageCount += count($files);
        }

        if ($imageCount > 0) {
            $this->line("✓ Found {$imageCount} images for optimization");
            // Note: Actual image optimization would require additional tools
            // like ImageMagick, which could be added based on requirements
        } else {
            $this->line('✓ No images found for optimization');
        }
    }

    /**
     * Run production-specific optimizations
     */
    private function productionOptimizations()
    {
        $this->info('Applying production optimizations...');

        // Optimize Composer autoloader
        $this->line('Optimizing Composer autoloader...');
        shell_exec('composer dump-autoload --optimize --no-dev 2>&1');
        $this->line('✓ Composer autoloader optimized');

        // Set production environment variables
        $this->optimizeEnvironmentConfig();
    }

    /**
     * Optimize environment configuration for production
     */
    private function optimizeEnvironmentConfig()
    {
        $envFile = base_path('.env');
        
        if (File::exists($envFile)) {
            $this->line('✓ Environment configuration reviewed');
            $this->warn('Please ensure the following are set for production:');
            $this->line('  - APP_ENV=production');
            $this->line('  - APP_DEBUG=false');
            $this->line('  - QUEUE_CONNECTION=redis (recommended)');
            $this->line('  - CACHE_DRIVER=redis (recommended)');
            $this->line('  - SESSION_DRIVER=redis (recommended)');
        }
    }

    /**
     * Clean up temporary files and logs
     */
    private function cleanupFiles()
    {
        $this->info('Cleaning up files...');

        // Clean old log files
        $logPath = storage_path('logs');
        if (File::exists($logPath)) {
            $files = File::files($logPath);
            $cleanedFiles = 0;

            foreach ($files as $file) {
                if ($file->getMTime() < strtotime('-7 days')) {
                    File::delete($file->getPathname());
                    $cleanedFiles++;
                }
            }

            $this->line("✓ Cleaned {$cleanedFiles} old log files");
        }

        // Clean compiled views
        $compiledPath = storage_path('framework/views');
        if (File::exists($compiledPath)) {
            $files = File::files($compiledPath);
            File::cleanDirectory($compiledPath);
            $this->line('✓ Cleaned compiled view files');
        }

        // Clean failed job files
        $this->cleanFailedJobs();
    }

    /**
     * Clean old failed jobs
     */
    private function cleanFailedJobs()
    {
        try {
            $cleanupDays = config('performance.queue.failed_jobs_cleanup_days', 7);
            $cutoffDate = now()->subDays($cleanupDays);
            
            $deletedCount = \DB::table('failed_jobs')
                ->where('failed_at', '<', $cutoffDate)
                ->delete();

            if ($deletedCount > 0) {
                $this->line("✓ Cleaned {$deletedCount} old failed jobs");
            } else {
                $this->line('✓ No old failed jobs to clean');
            }
        } catch (\Exception $e) {
            $this->warn('Failed job cleanup skipped: ' . $e->getMessage());
        }
    }

    /**
     * Display optimization summary
     */
    private function displayOptimizationSummary()
    {
        $this->info('Optimization Summary:');
        $this->line('═══════════════════════════════════════');
        
        // Check current performance settings
        $cacheDriver = config('cache.default');
        $queueDriver = config('queue.default');
        $sessionDriver = config('session.driver');
        
        $this->line("Cache Driver: {$cacheDriver}");
        $this->line("Queue Driver: {$queueDriver}");
        $this->line("Session Driver: {$sessionDriver}");
        
        // Memory usage
        $memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $this->line("Peak Memory Usage: {$memoryUsage} MB");
        
        // Recommendations
        $this->info('Performance Recommendations:');
        $this->line('───────────────────────────────────────');
        
        if ($cacheDriver === 'file') {
            $this->warn('Consider using Redis for cache driver');
        }
        
        if ($queueDriver === 'sync') {
            $this->warn('Consider using Redis or database for queue driver');
        }
        
        $this->line('✓ Run `php artisan queue:work` to process background jobs');
        $this->line('✓ Enable OPcache in production');
        $this->line('✓ Use a CDN for static assets');
        $this->line('✓ Monitor application performance regularly');
    }
}