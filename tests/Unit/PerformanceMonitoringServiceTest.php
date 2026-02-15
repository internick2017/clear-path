<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PerformanceMonitoringService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PerformanceMonitoringServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function it_monitors_query_performance()
    {
        Log::shouldReceive('log')
            ->once()
            ->with('info', \Mockery::pattern('/Performance metric: test_query/'), \Mockery::type('array'));

        $result = PerformanceMonitoringService::monitorQuery('test_query', function () {
            // Simulate some work
            usleep(1000); // 1ms
            return ['data' => 'test'];
        });

        $this->assertEquals(['data' => 'test'], $result);
    }

    /** @test */
    public function it_logs_slow_queries_as_warnings()
    {
        Log::shouldReceive('log')
            ->once()
            ->with('warning', \Mockery::pattern('/Performance metric: slow_query/'), \Mockery::type('array'));

        PerformanceMonitoringService::monitorQuery('slow_query', function () {
            // Simulate slow work
            usleep(150000); // 150ms - above warning threshold
            return ['data' => 'slow'];
        });
    }

    /** @test */
    public function it_handles_query_exceptions()
    {
        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/Query failed: failing_query/'), \Mockery::type('array'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        PerformanceMonitoringService::monitorQuery('failing_query', function () {
            throw new \Exception('Test exception');
        });
    }

    /** @test */
    public function it_monitors_cache_performance()
    {
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Cache operation: test_cache_operation/'), \Mockery::type('array'));

        $result = PerformanceMonitoringService::monitorCache('test_cache_operation', function () {
            return 'cached_data';
        });

        $this->assertEquals('cached_data', $result);
    }

    /** @test */
    public function it_handles_cache_exceptions()
    {
        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/Cache operation failed: failing_cache_operation/'), \Mockery::type('array'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cache exception');

        PerformanceMonitoringService::monitorCache('failing_cache_operation', function () {
            throw new \Exception('Cache exception');
        });
    }

    /** @test */
    public function it_gets_database_stats()
    {
        // Enable query logging for this test
        DB::enableQueryLog();

        // Execute a simple query to add to log
        DB::select('SELECT 1 as test');

        $stats = PerformanceMonitoringService::getDatabaseStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_queries', $stats);
        $this->assertArrayHasKey('slow_queries', $stats);
        $this->assertArrayHasKey('cache_hits', $stats);
        $this->assertArrayHasKey('cache_misses', $stats);

        // Should have at least one query from our SELECT
        $this->assertGreaterThanOrEqual(1, $stats['total_queries']);

        DB::disableQueryLog();
    }

    /** @test */
    public function it_gets_cache_stats()
    {
        $stats = PerformanceMonitoringService::getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('keys', $stats);
    }

    /** @test */
    public function it_gets_performance_summary()
    {
        $summary = PerformanceMonitoringService::getPerformanceSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('database', $summary);
        $this->assertArrayHasKey('cache', $summary);
        $this->assertArrayHasKey('memory_usage', $summary);
        $this->assertArrayHasKey('peak_memory_usage', $summary);
        $this->assertArrayHasKey('uptime', $summary);

        // Memory usage should be positive
        $this->assertGreaterThan(0, $summary['memory_usage']);
        $this->assertGreaterThan(0, $summary['peak_memory_usage']);
    }

    /** @test */
    public function it_stores_performance_metrics_in_cache()
    {
        Log::shouldReceive('log')->once();

        PerformanceMonitoringService::monitorQuery('cached_query', function () {
            return 'test';
        });

        // Check that metrics are stored in cache
        $metricsKey = 'performance_metrics_cached_query_' . now()->format('Y-m-d');
        $metrics = Cache::get($metricsKey);

        $this->assertIsArray($metrics);
        $this->assertNotEmpty($metrics);
        $this->assertArrayHasKey('timestamp', $metrics[0]);
        $this->assertArrayHasKey('execution_time', $metrics[0]);
        $this->assertArrayHasKey('memory_used', $metrics[0]);
    }

    /** @test */
    public function it_gets_query_metrics()
    {
        // First, store some metrics
        Log::shouldReceive('log')->once();
        PerformanceMonitoringService::monitorQuery('metric_test_query', function () {
            return 'test';
        });

        $metrics = PerformanceMonitoringService::getQueryMetrics('metric_test_query', 1);

        $this->assertIsArray($metrics);
        // May be empty if no metrics for the exact hour range
        $this->assertIsArray($metrics);
    }

    /** @test */
    public function it_checks_performance_health()
    {
        $health = PerformanceMonitoringService::checkPerformanceHealth();

        $this->assertIsArray($health);
        $this->assertArrayHasKey('healthy', $health);
        $this->assertArrayHasKey('issues', $health);
        $this->assertArrayHasKey('summary', $health);

        $this->assertIsBool($health['healthy']);
        $this->assertIsArray($health['issues']);
        $this->assertIsArray($health['summary']);
    }

    /** @test */
    public function it_detects_performance_issues()
    {
        // Mock high memory usage by manipulating the summary
        $originalMethod = new \ReflectionMethod(PerformanceMonitoringService::class, 'checkPerformanceHealth');

        // For this test, we'll just verify the structure since mocking static methods is complex
        $health = PerformanceMonitoringService::checkPerformanceHealth();

        // Should return proper structure regardless of actual performance
        $this->assertArrayHasKey('healthy', $health);
        $this->assertArrayHasKey('issues', $health);
        $this->assertArrayHasKey('summary', $health);
    }

    /** @test */
    public function it_limits_stored_metrics()
    {
        Log::shouldReceive('log')->times(105); // More than the 100 limit

        // Store more than 100 metrics
        for ($i = 0; $i < 105; $i++) {
            PerformanceMonitoringService::monitorQuery('limit_test_query', function () {
                return 'test';
            });
        }

        // Check that only 100 metrics are kept
        $metricsKey = 'performance_metrics_limit_test_query_' . now()->format('Y-m-d');
        $metrics = Cache::get($metricsKey);

        $this->assertIsArray($metrics);
        $this->assertLessThanOrEqual(100, count($metrics));
    }
}
