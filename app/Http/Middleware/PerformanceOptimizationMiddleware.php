<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceOptimizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $response = $next($request);

        // Calculate execution time and memory usage
        $executionTime = (microtime(true) - $startTime) * 1000; // in milliseconds
        $memoryUsage = memory_get_usage() - $startMemory;
        $peakMemory = memory_get_peak_usage();

        // Add performance headers for debugging (only in development)
        if (config('app.debug')) {
            $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
            $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsage));
            $response->headers->set('X-Peak-Memory', $this->formatBytes($peakMemory));
        }

        // Log slow requests
        if ($executionTime > config('performance.logging.slow_request_threshold', 1000)) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => $executionTime . 'ms',
                'memory_usage' => $this->formatBytes($memoryUsage),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip()
            ]);
        }

        // Apply performance optimizations to the response
        $this->optimizeResponse($response, $request);

        return $response;
    }

    /**
     * Optimize the HTTP response
     *
     * @param Response $response
     * @param Request $request
     * @return void
     */
    private function optimizeResponse(Response $response, Request $request): void
    {
        // Enable Gzip compression if supported and enabled
        if (config('performance.response.gzip_compression', true) && 
            $this->supportsGzip($request)) {
            $response->headers->set('Content-Encoding', 'gzip');
        }

        // Add cache headers for static assets
        if ($this->isStaticAsset($request)) {
            $ttl = config('performance.response.browser_cache_ttl', 86400);
            $response->headers->set('Cache-Control', "public, max-age={$ttl}");
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $ttl));
        }

        // Add ETags for better caching
        if (config('performance.response.etag_enabled', true)) {
            $etag = md5($response->getContent());
            $response->headers->set('ETag', '"' . $etag . '"');

            // Check if client has cached version
            if ($request->header('If-None-Match') === '"' . $etag . '"') {
                $response->setStatusCode(304);
                $response->setContent('');
            }
        }

        // Add security and performance headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Add DNS prefetch for external resources
        $response->headers->set('X-DNS-Prefetch-Control', 'on');
    }

    /**
     * Check if request accepts Gzip compression
     *
     * @param Request $request
     * @return bool
     */
    private function supportsGzip(Request $request): bool
    {
        $acceptEncoding = $request->header('Accept-Encoding');
        return $acceptEncoding && strpos($acceptEncoding, 'gzip') !== false;
    }

    /**
     * Check if request is for a static asset
     *
     * @param Request $request
     * @return bool
     */
    private function isStaticAsset(Request $request): bool
    {
        $staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'woff', 'woff2', 'ttf'];
        $extension = pathinfo($request->path(), PATHINFO_EXTENSION);
        return in_array(strtolower($extension), $staticExtensions);
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}