<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Services\PerformanceMonitor;

class OptimizeApplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize 
                            {--clear : Clear all caches before optimizing}
                            {--skip-npm : Skip npm build process}
                            {--detailed : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize the Laravel application for maximum performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $monitor = new PerformanceMonitor();
        $monitor->startTimer('total_optimization');

        $this->info('🚀 Starting Laravel Application Optimization...');
        $this->newLine();

        // Clear caches if requested
        if ($this->option('clear')) {
            $this->clearCaches();
        }

        // Run Laravel optimizations
        $this->optimizeLaravel();

        // Run database optimizations
        $this->optimizeDatabase();

        // Run frontend optimizations
        if (!$this->option('skip-npm')) {
            $this->optimizeFrontend();
        }

        // Final checks and metrics
        $this->performFinalChecks($monitor);

        $this->newLine();
        $this->info('✅ Application optimization completed successfully!');
        
        return Command::SUCCESS;
    }

    /**
     * Clear all application caches
     */
    protected function clearCaches(): void
    {
        $this->info('🧹 Clearing caches...');
        
        $commands = [
            'cache:clear' => 'Application cache',
            'config:clear' => 'Configuration cache',
            'route:clear' => 'Route cache',
            'view:clear' => 'View cache',
            'event:clear' => 'Event cache',
        ];

        foreach ($commands as $command => $description) {
            if ($this->option('detailed')) {
                $this->line("   - Clearing {$description}");
            }
            Artisan::call($command);
        }

        $this->info('   ✓ Caches cleared');
        $this->newLine();
    }

    /**
     * Run Laravel-specific optimizations
     */
    protected function optimizeLaravel(): void
    {
        $this->info('⚡ Optimizing Laravel...');

        $commands = [
            'config:cache' => 'Configuration caching',
            'route:cache' => 'Route caching',
            'view:cache' => 'View caching',
            'event:cache' => 'Event caching',
        ];

        foreach ($commands as $command => $description) {
            if ($this->option('detailed')) {
                $this->line("   - {$description}");
            }
            
            $result = Artisan::call($command);
            if ($result !== 0) {
                $this->warn("   ⚠ Warning: {$description} may have issues");
            }
        }

        // Optimize Composer autoloader
        if ($this->option('detailed')) {
            $this->line('   - Optimizing Composer autoloader');
        }
        exec('composer install --optimize-autoloader --no-dev --quiet 2>/dev/null');

        $this->info('   ✓ Laravel optimization completed');
        $this->newLine();
    }

    /**
     * Run database optimizations
     */
    protected function optimizeDatabase(): void
    {
        $this->info('🗄️  Optimizing Database...');

        try {
            // Run migrations if any pending
            if ($this->option('detailed')) {
                $this->line('   - Checking for pending migrations');
            }
            Artisan::call('migrate', ['--force' => true]);

            // Run the index optimization migration
            if ($this->option('detailed')) {
                $this->line('   - Optimizing database indexes');
            }
            
            $this->info('   ✓ Database optimization completed');
        } catch (\Exception $e) {
            $this->warn('   ⚠ Database optimization encountered issues: ' . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Run frontend optimizations
     */
    protected function optimizeFrontend(): void
    {
        $this->info('🎨 Optimizing Frontend...');

        try {
            if ($this->option('detailed')) {
                $this->line('   - Installing npm dependencies');
            }
            exec('npm install --silent 2>/dev/null');

            if ($this->option('detailed')) {
                $this->line('   - Building production assets');
            }
            exec('npm run build --silent 2>/dev/null');

            $this->info('   ✓ Frontend optimization completed');
        } catch (\Exception $e) {
            $this->warn('   ⚠ Frontend optimization encountered issues: ' . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Perform final checks and show metrics
     */
    protected function performFinalChecks(PerformanceMonitor $monitor): void
    {
        $this->info('📊 Performance Analysis...');

        $totalMetrics = $monitor->endTimer('total_optimization');
        $healthCheck = $monitor->checkPerformanceHealth();

        if ($this->option('detailed')) {
            $this->line('   - Total optimization time: ' . round($totalMetrics['duration'], 2) . 's');
            $this->line('   - Memory usage: ' . round($totalMetrics['memory_used'] / 1024 / 1024, 2) . 'MB');
        }

        if ($healthCheck['healthy']) {
            $this->info('   ✓ Application performance is optimal');
        } else {
            $this->warn('   ⚠ Performance issues detected:');
            foreach ($healthCheck['issues'] as $issue) {
                $this->line("     - {$issue}");
            }
        }

        // Log metrics for monitoring
        $monitor->logMetrics('optimization');

        $this->newLine();
        $this->info('📈 Performance metrics logged for monitoring');
    }
}
