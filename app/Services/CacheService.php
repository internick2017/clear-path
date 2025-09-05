<?php

namespace App\Services;

use App\Models\User;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CacheService
{
    const CACHE_TTL = 3600; // 1 hour
    const DASHBOARD_CACHE_TTL = 1800; // 30 minutes
    const USER_DATA_CACHE_TTL = 900; // 15 minutes

    /**
     * Get or set dashboard data with caching
     */
    public static function getDashboardData(User $user): array
    {
        $cacheKey = "dashboard_data_{$user->id}_" . now()->format('Y-m');

        return Cache::remember($cacheKey, self::DASHBOARD_CACHE_TTL, function () use ($user) {
            return self::generateDashboardData($user);
        });
    }

    /**
     * Get or set user transactions with caching
     */
    public static function getUserTransactions(User $user, array $filters = []): array
    {
        $cacheKey = "user_transactions_{$user->id}_" . md5(serialize($filters));

        return Cache::remember($cacheKey, self::USER_DATA_CACHE_TTL, function () use ($user, $filters) {
            return self::generateUserTransactions($user, $filters);
        });
    }

    /**
     * Get or set user budgets with caching
     */
    public static function getUserBudgets(User $user, string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        $cacheKey = "user_budgets_{$user->id}_{$month}";

        return Cache::remember($cacheKey, self::USER_DATA_CACHE_TTL, function () use ($user, $month) {
            return self::generateUserBudgets($user, $month);
        });
    }

    /**
     * Get or set user goals with caching
     */
    public static function getUserGoals(User $user): array
    {
        $cacheKey = "user_goals_{$user->id}";

        return Cache::remember($cacheKey, self::USER_DATA_CACHE_TTL, function () use ($user) {
            return self::generateUserGoals($user);
        });
    }

    /**
     * Get or set user debts with caching
     */
    public static function getUserDebts(User $user): array
    {
        $cacheKey = "user_debts_{$user->id}";

        return Cache::remember($cacheKey, self::USER_DATA_CACHE_TTL, function () use ($user) {
            return self::generateUserDebts($user);
        });
    }

    /**
     * Clear all cache for a user
     */
    public static function clearUserCache(User $user): void
    {
        $patterns = [
            "dashboard_data_{$user->id}_*",
            "user_transactions_{$user->id}_*",
            "user_budgets_{$user->id}_*",
            "user_goals_{$user->id}",
            "user_debts_{$user->id}",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Clear specific cache by pattern
     */
    public static function clearCacheByPattern(string $pattern): void
    {
        Cache::forget($pattern);
    }

    /**
     * Generate dashboard data with optimized queries
     */
    private static function generateDashboardData(User $user): array
    {
        // Use QueryOptimizationService for better performance
        $monthlySummary = QueryOptimizationService::getMonthlySummary($user);
        $topExpenseCategories = QueryOptimizationService::getTopExpenseCategories($user);
        $budgets = QueryOptimizationService::getBudgetData($user);
        $activeGoals = QueryOptimizationService::getGoalData($user);
        $activeDebts = QueryOptimizationService::getDebtData($user);
        $recentTransactions = QueryOptimizationService::getRecentTransactions($user);

        return [
            'monthlySummary' => $monthlySummary,
            'topExpenseCategories' => $topExpenseCategories,
            'budgets' => $budgets,
            'activeGoals' => $activeGoals,
            'activeDebts' => $activeDebts,
            'recentTransactions' => $recentTransactions,
        ];
    }

    /**
     * Generate user transactions with optimized queries
     */
    private static function generateUserTransactions(User $user, array $filters = []): array
    {
        $query = $user->transactions()->orderBy('date', 'desc');

        // Apply filters
        if (!empty($filters['month'])) {
            $query->whereMonth('date', $filters['month']);
        }
        if (!empty($filters['year'])) {
            $query->whereYear('date', $filters['year']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        $transactions = $query->paginate(15);

        // Get categories for filter dropdown
        $categories = $user->transactions()
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return [
            'transactions' => $transactions,
            'categories' => $categories,
        ];
    }

    /**
     * Generate user budgets with optimized queries
     */
    private static function generateUserBudgets(User $user, string $month): array
    {
        $date = Carbon::createFromFormat('Y-m', $month);

        $budgets = $user->budgets()
            ->whereMonth('month', $date->month)
            ->whereYear('month', $date->year)
            ->get();

        return [
            'budgets' => $budgets,
            'month' => $month,
        ];
    }

    /**
     * Generate user goals with optimized queries
     */
    private static function generateUserGoals(User $user): array
    {
        $goals = $user->goals()
            ->orderBy('deadline', 'asc')
            ->get();

        return [
            'goals' => $goals,
        ];
    }

    /**
     * Generate user debts with optimized queries
     */
    private static function generateUserDebts(User $user): array
    {
        $debts = $user->debts()
            ->orderBy('due_date', 'asc')
            ->get();

        return [
            'debts' => $debts,
        ];
    }
}