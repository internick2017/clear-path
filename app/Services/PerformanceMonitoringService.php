<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PerformanceMonitoringService
{
    /**
     * Monitor database query performance
     */
    public static function monitorQuery(string $queryName, callable $callback)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        try {
            $result = $callback();

            $endTime = microtime(true);
            $endMemory = memory_get_usage();

            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            $memoryUsed = $endMemory - $startMemory;

            // Log performance metrics
            self::logPerformanceMetrics($queryName, $executionTime, $memoryUsed);

            return $result;
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;

            Log::error("Query failed: {$queryName}", [
                'execution_time_ms' => $executionTime,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Monitor cache performance
     */
    public static function monitorCache(string $operation, callable $callback)
    {
        $startTime = microtime(true);

        try {
            $result = $callback();

            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;

            Log::info("Cache operation: {$operation}", [
                'execution_time_ms' => $executionTime,
                'cache_hit' => $result !== null,
            ]);

            return $result;
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;

            Log::error("Cache operation failed: {$operation}", [
                'execution_time_ms' => $executionTime,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get database performance statistics
     */
    public static function getDatabaseStats(): array
    {
        $stats = [
            'total_queries' => 0,
            'slow_queries' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
        ];

        // Get query log if enabled
        if (config('app.debug')) {
            $queries = DB::getQueryLog();
            $stats['total_queries'] = count($queries);

            foreach ($queries as $query) {
                if ($query['time'] > 100) { // Queries taking more than 100ms
                    $stats['slow_queries']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Get cache performance statistics
     */
    public static function getCacheStats(): array
    {
        $driver = config('cache.default');

        $stats = [
            'driver' => $driver,
            'hits' => 0,
            'misses' => 0,
            'keys' => 0,
        ];

        // Try to get cache statistics based on driver
        try {
            if ($driver === 'redis') {
                $redis = Cache::getRedis();
                $info = $redis->info('stats');

                $stats['hits'] = $info['keyspace_hits'] ?? 0;
                $stats['misses'] = $info['keyspace_misses'] ?? 0;
                $stats['keys'] = $info['db0'] ?? 0;
            } elseif ($driver === 'file') {
                // For file cache, we can count files in cache directory
                $cachePath = storage_path('framework/cache');
                if (is_dir($cachePath)) {
                    $stats['keys'] = count(glob($cachePath . '/*'));
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not retrieve cache statistics', [
                'driver' => $driver,
                'error' => $e->getMessage(),
            ]);
        }

        return $stats;
    }

    /**
     * Get application performance summary
     */
    public static function getPerformanceSummary(): array
    {
        return [
            'database' => self::getDatabaseStats(),
            'cache' => self::getCacheStats(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory_usage' => memory_get_peak_usage(true),
            'uptime' => time() - LARAVEL_START,
        ];
    }

    /**
     * Log performance metrics
     */
    private static function logPerformanceMetrics(string $queryName, float $executionTime, int $memoryUsed): void
    {
        $logLevel = $executionTime > 100 ? 'warning' : 'info';

        Log::log($logLevel, "Performance metric: {$queryName}", [
            'execution_time_ms' => round($executionTime, 2),
            'memory_used_bytes' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
        ]);

        // Store metrics in cache for aggregation
        $metricsKey = "performance_metrics_{$queryName}_" . now()->format('Y-m-d');
        $metrics = Cache::get($metricsKey, []);

        $metrics[] = [
            'timestamp' => now()->timestamp,
            'execution_time' => $executionTime,
            'memory_used' => $memoryUsed,
        ];

        // Keep only last 100 metrics
        if (count($metrics) > 100) {
            $metrics = array_slice($metrics, -100);
        }

        Cache::put($metricsKey, $metrics, 3600); // Store for 1 hour
    }

    /**
     * Get performance metrics for a specific query
     */
    public static function getQueryMetrics(string $queryName, int $hours = 24): array
    {
        $metrics = [];
        $startDate = now()->subHours($hours);

        for ($i = 0; $i < $hours; $i++) {
            $date = $startDate->copy()->addHours($i);
            $key = "performance_metrics_{$queryName}_" . $date->format('Y-m-d');
            $hourMetrics = Cache::get($key, []);

            // Filter metrics for this hour
            $hourMetrics = array_filter($hourMetrics, function ($metric) use ($date) {
                return $metric['timestamp'] >= $date->timestamp &&
                       $metric['timestamp'] < $date->copy()->addHour()->timestamp;
            });

            if (!empty($hourMetrics)) {
                $avgExecutionTime = array_sum(array_column($hourMetrics, 'execution_time')) / count($hourMetrics);
                $avgMemoryUsed = array_sum(array_column($hourMetrics, 'memory_used')) / count($hourMetrics);

                $metrics[] = [
                    'hour' => $date->format('H:00'),
                    'count' => count($hourMetrics),
                    'avg_execution_time' => round($avgExecutionTime, 2),
                    'avg_memory_used' => round($avgMemoryUsed / 1024 / 1024, 2),
                ];
            }
        }

        return $metrics;
    }

    /**
     * Check if performance is within acceptable limits
     */
    public static function checkPerformanceHealth(): array
    {
        $summary = self::getPerformanceSummary();
        $issues = [];

        // Check database performance
        if ($summary['database']['slow_queries'] > 0) {
            $issues[] = "Found {$summary['database']['slow_queries']} slow queries";
        }

        // Check memory usage
        $memoryUsageMB = $summary['memory_usage'] / 1024 / 1024;
        if ($memoryUsageMB > 512) { // More than 512MB
            $issues[] = "High memory usage: " . round($memoryUsageMB, 2) . "MB";
        }

        // Check cache hit rate
        $totalCacheRequests = $summary['cache']['hits'] + $summary['cache']['misses'];
        if ($totalCacheRequests > 0) {
            $hitRate = ($summary['cache']['hits'] / $totalCacheRequests) * 100;
            if ($hitRate < 50) {
                $issues[] = "Low cache hit rate: " . round($hitRate, 2) . "%";
            }
        }

        return [
            'healthy' => empty($issues),
            'issues' => $issues,
            'summary' => $summary,
        ];
    }
}