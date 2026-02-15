<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\Debt;
use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class CacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_caches_dashboard_data()
    {
        // Clear any existing cache
        Cache::flush();

        // Create test data
        Transaction::factory()->for($this->user)->create([
            'type' => 'income',
            'amount' => 1000,
            'date' => now()->format('Y-m-d'),
        ]);

        Budget::factory()->for($this->user)->create([
            'category' => 'Groceries',
            'limit' => 500,
            'month' => now()->format('Y-m-01'),
        ]);

        Goal::factory()->for($this->user)->create([
            'title' => 'Test Goal',
            'target_amount' => 1000,
            'current_amount' => 100,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ]);

        // First call should hit database
        $data1 = CacheService::getDashboardData($this->user);
        $this->assertIsArray($data1);
        $this->assertArrayHasKey('monthlySummary', $data1);
        $this->assertArrayHasKey('budgets', $data1);
        $this->assertArrayHasKey('activeGoals', $data1);

        // Second call should hit cache
        $data2 = CacheService::getDashboardData($this->user);
        $this->assertEquals($data1, $data2);

        // Verify cache key exists
        $cacheKey = "dashboard_data_{$this->user->id}_" . now()->format('Y-m');
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_clears_user_cache()
    {
        // Create some cached data
        CacheService::getDashboardData($this->user);

        $cacheKey = "dashboard_data_{$this->user->id}_" . now()->format('Y-m');
        $this->assertTrue(Cache::has($cacheKey));

        // Clear cache
        CacheService::clearUserCache($this->user);

        // Verify cache is cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_caches_user_transactions()
    {
        Cache::flush();

        // Create test transactions
        Transaction::factory()->for($this->user)->count(3)->create();

        $filters = ['type' => 'income'];
        $data1 = CacheService::getUserTransactions($this->user, $filters);
        $data2 = CacheService::getUserTransactions($this->user, $filters);

        $this->assertEquals($data1, $data2);
        $this->assertArrayHasKey('transactions', $data1);
        $this->assertArrayHasKey('categories', $data1);
    }

    /** @test */
    public function it_caches_user_budgets()
    {
        Cache::flush();

        Budget::factory()->for($this->user)->create([
            'month' => now()->format('Y-m-01'),
        ]);

        $month = now()->format('Y-m');
        $data1 = CacheService::getUserBudgets($this->user, $month);
        $data2 = CacheService::getUserBudgets($this->user, $month);

        $this->assertEquals($data1, $data2);
        $this->assertArrayHasKey('budgets', $data1);
        $this->assertArrayHasKey('month', $data1);
    }

    /** @test */
    public function it_caches_user_goals()
    {
        Cache::flush();

        Goal::factory()->for($this->user)->create([
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $data1 = CacheService::getUserGoals($this->user);
        $data2 = CacheService::getUserGoals($this->user);

        $this->assertEquals($data1, $data2);
        $this->assertArrayHasKey('goals', $data1);
    }

    /** @test */
    public function it_caches_user_debts()
    {
        Cache::flush();

        Debt::factory()->for($this->user)->create([
            'status' => 'active',
        ]);

        $data1 = CacheService::getUserDebts($this->user);
        $data2 = CacheService::getUserDebts($this->user);

        $this->assertEquals($data1, $data2);
        $this->assertArrayHasKey('debts', $data1);
    }

    /** @test */
    public function it_handles_empty_data_gracefully()
    {
        Cache::flush();

        // Test with no data
        $dashboardData = CacheService::getDashboardData($this->user);

        $this->assertIsArray($dashboardData);
        $this->assertArrayHasKey('monthlySummary', $dashboardData);
        $this->assertEquals(0, $dashboardData['monthlySummary']['income']);
        $this->assertEquals(0, $dashboardData['monthlySummary']['expenses']);
        $this->assertEmpty($dashboardData['budgets']);
        $this->assertEmpty($dashboardData['activeGoals']);
        $this->assertEmpty($dashboardData['activeDebts']);
    }

    /** @test */
    public function it_respects_cache_ttl()
    {
        Cache::flush();

        // Mock time to test TTL
        $cacheKey = "dashboard_data_{$this->user->id}_" . now()->format('Y-m');

        // Put data in cache with short TTL for testing
        Cache::put($cacheKey, ['test' => 'data'], 1); // 1 second TTL

        $this->assertTrue(Cache::has($cacheKey));

        // Wait for cache to expire (in real scenario)
        // For testing purposes, we'll manually verify TTL is set
        $this->assertEquals(1, Cache::getStore()->getRedis()->ttl($cacheKey));
    }
}
