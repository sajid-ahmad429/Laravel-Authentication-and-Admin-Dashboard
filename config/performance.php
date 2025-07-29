<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization Settings
    |--------------------------------------------------------------------------
    |
    | This file contains all performance-related configuration options
    | for your Laravel application.
    |
    */

    'cache' => [
        /*
         * Cache configuration for better performance
         */
        'query_cache_ttl' => env('QUERY_CACHE_TTL', 3600), // 1 hour
        'view_cache_enabled' => env('VIEW_CACHE_ENABLED', true),
        'route_cache_enabled' => env('ROUTE_CACHE_ENABLED', true),
        'config_cache_enabled' => env('CONFIG_CACHE_ENABLED', true),
    ],

    'database' => [
        /*
         * Database optimization settings
         */
        'connection_pool_size' => env('DB_POOL_SIZE', 10),
        'query_timeout' => env('DB_QUERY_TIMEOUT', 30),
        'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 2000), // milliseconds
        'enable_query_log' => env('DB_ENABLE_QUERY_LOG', false),
    ],

    'queue' => [
        /*
         * Queue optimization settings
         */
        'default_queue' => env('QUEUE_DEFAULT', 'default'),
        'email_queue' => env('QUEUE_EMAIL', 'emails'),
        'bulk_email_queue' => env('QUEUE_BULK_EMAIL', 'bulk-emails'),
        'high_priority_queue' => env('QUEUE_HIGH_PRIORITY', 'high'),
        'worker_timeout' => env('QUEUE_WORKER_TIMEOUT', 60),
        'worker_sleep' => env('QUEUE_WORKER_SLEEP', 3),
        'worker_tries' => env('QUEUE_WORKER_TRIES', 3),
        'failed_jobs_cleanup_days' => env('QUEUE_FAILED_CLEANUP_DAYS', 7),
    ],

    'session' => [
        /*
         * Session optimization settings
         */
        'optimize_session_storage' => env('SESSION_OPTIMIZE_STORAGE', true),
        'session_gc_probability' => env('SESSION_GC_PROBABILITY', 1),
        'session_gc_divisor' => env('SESSION_GC_DIVISOR', 100),
        'session_gc_maxlifetime' => env('SESSION_GC_MAXLIFETIME', 1440),
    ],

    'email' => [
        /*
         * Email sending optimization
         */
        'queue_emails' => env('MAIL_QUEUE_EMAILS', true),
        'batch_size' => env('MAIL_BATCH_SIZE', 50),
        'rate_limit_per_minute' => env('MAIL_RATE_LIMIT', 100),
        'retry_attempts' => env('MAIL_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('MAIL_RETRY_DELAY', 60), // seconds
    ],

    'assets' => [
        /*
         * Asset optimization settings
         */
        'enable_compression' => env('ASSET_COMPRESSION', true),
        'enable_minification' => env('ASSET_MINIFICATION', true),
        'cache_busting' => env('ASSET_CACHE_BUSTING', true),
        'lazy_loading' => env('ASSET_LAZY_LOADING', true),
        'image_optimization' => env('IMAGE_OPTIMIZATION', true),
        'webp_conversion' => env('WEBP_CONVERSION', true),
    ],

    'logging' => [
        /*
         * Logging optimization settings
         */
        'log_level' => env('LOG_LEVEL', 'error'),
        'log_rotation' => env('LOG_ROTATION', true),
        'log_max_files' => env('LOG_MAX_FILES', 5),
        'log_queries' => env('LOG_QUERIES', false),
        'log_slow_requests' => env('LOG_SLOW_REQUESTS', true),
        'slow_request_threshold' => env('SLOW_REQUEST_THRESHOLD', 1000), // milliseconds
    ],

    'memory' => [
        /*
         * Memory optimization settings
         */
        'memory_limit' => env('MEMORY_LIMIT', '256M'),
        'max_execution_time' => env('MAX_EXECUTION_TIME', 60),
        'garbage_collection' => env('ENABLE_GARBAGE_COLLECTION', true),
        'opcache_enabled' => env('OPCACHE_ENABLED', true),
    ],

    'response' => [
        /*
         * Response optimization settings
         */
        'gzip_compression' => env('RESPONSE_GZIP', true),
        'etag_enabled' => env('RESPONSE_ETAG', true),
        'cache_headers' => env('RESPONSE_CACHE_HEADERS', true),
        'browser_cache_ttl' => env('BROWSER_CACHE_TTL', 86400), // 24 hours
    ],

];