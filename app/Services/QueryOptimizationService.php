<?php

namespace App\Services;

use App\Helpers\CurrencyHelper;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\Debt;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QueryOptimizationService
{
    /**
     * Get optimized monthly summary with single query
     */
    public static function getMonthlySummary(User $user, int $month = null, int $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        try {
            // Get user's display currency - use user parameter directly
            $userCurrency = $user->display_currency ?? config('currencies.default', 'USD');

            $summary = DB::table('transactions')
                ->selectRaw('
                    SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expenses,
                    COUNT(*) as total_transactions
                ')
                ->where('user_id', $user->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->first();

            // Handle null summary or empty results
            if (!$summary) {
                return [
                    'income' => 0.0,
                    'expenses' => 0.0,
                    'net' => 0.0,
                    'total_transactions' => 0,
                ];
            }

            // Convert amounts from base currency to user's display currency
            $income = CurrencyHelper::convertStoredAmount((float) ($summary->total_income ?? 0), $userCurrency);
            $expenses = CurrencyHelper::convertStoredAmount((float) ($summary->total_expenses ?? 0), $userCurrency);

            return [
                'income' => $income,
                'expenses' => $expenses,
                'net' => $income - $expenses,
                'total_transactions' => (int) ($summary->total_transactions ?? 0),
            ];
        } catch (\Exception $e) {
            return [
                'income' => 0.0,
                'expenses' => 0.0,
                'net' => 0.0,
                'total_transactions' => 0,
            ];
        }
    }

    /**
     * Get optimized top expense categories with single query
     */
    public static function getTopExpenseCategories(User $user, int $month = null, int $year = null, int $limit = 5): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        try {
            // Get user's display currency - use user parameter directly
            $userCurrency = $user->display_currency ?? config('currencies.default', 'USD');

            $results = DB::table('transactions')
                ->selectRaw('category, SUM(amount) as total_amount, COUNT(*) as transaction_count')
                ->where('user_id', $user->id)
                ->where('type', 'expense')
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->groupBy('category')
                ->orderByDesc('total_amount')
                ->limit($limit)
                ->get();

            if ($results->isEmpty()) {
                return [];
            }

            return $results->map(function ($item) use ($userCurrency) {
                return [
                    'category' => $item->category ?? 'Sin categoría',
                    'total' => CurrencyHelper::convertStoredAmount((float) ($item->total_amount ?? 0), $userCurrency),
                    'transaction_count' => (int) ($item->transaction_count ?? 0),
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get optimized budget data with single query
     */
    public static function getBudgetData(User $user, int $month = null, int $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        try {
            // Get budgets with actual spending in single query
            $results = DB::table('budgets as b')
                ->selectRaw('
                    b.id,
                    b.category,
                    b.limit,
                    b.spent as budget_spent,
                    COALESCE(SUM(t.amount), 0) as actual_spent
                ')
                ->leftJoin('transactions as t', function ($join) use ($month, $year, $user) {
                    $join->on('b.category', '=', 't.category')
                        ->where('t.user_id', '=', $user->id)
                        ->where('t.type', '=', 'expense')
                        ->whereMonth('t.date', $month)
                        ->whereYear('t.date', $year);
                })
                ->where('b.user_id', $user->id)
                ->whereMonth('b.month', $month)
                ->whereYear('b.month', $year)
                ->groupBy('b.id', 'b.category', 'b.limit', 'b.spent')
                ->get();

            if ($results->isEmpty()) {
                return [];
            }

            // Get user's display currency - use user parameter directly
            $userCurrency = $user->display_currency ?? config('currencies.default', 'USD');

            return $results->map(function ($budget) use ($userCurrency) {
                $actualSpent = (float) ($budget->actual_spent ?? 0);
                $budgetSpent = (float) ($budget->budget_spent ?? 0);
                $spent = max($actualSpent, $budgetSpent);
                $limit = (float) ($budget->limit ?? 0);

                // Convert all amounts to user's display currency
                $convertedLimit = CurrencyHelper::convertStoredAmount($limit, $userCurrency);
                $convertedSpent = CurrencyHelper::convertStoredAmount($spent, $userCurrency);

                return [
                    'id' => $budget->id,
                    'category' => $budget->category ?? 'Sin categoría',
                    'limit' => $convertedLimit,
                    'spent' => $convertedSpent,
                    'remaining' => $convertedLimit - $convertedSpent,
                    'percentage' => $convertedLimit > 0 ? ($convertedSpent / $convertedLimit) * 100 : 0,
                    'is_exceeded' => $convertedSpent > $convertedLimit,
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get optimized goal data with single query
     */
    public static function getGoalData(User $user): array
    {
        try {
            $results = DB::table('goals')
                ->select('id', 'title', 'target_amount', 'current_amount', 'deadline')
                ->where('user_id', $user->id)
                ->whereRaw('CAST(COALESCE(current_amount, 0) AS DECIMAL(10,2)) < CAST(target_amount AS DECIMAL(10,2))')
                ->where('deadline', '>', now()->format('Y-m-d'))
                ->orderBy('deadline', 'asc')
                ->get();

            if ($results->isEmpty()) {
                return [];
            }

            // Get user's display currency - use user parameter directly
            $userCurrency = $user->display_currency ?? config('currencies.default', 'USD');

            return $results->map(function ($goal) use ($userCurrency) {
                $targetAmount = (float) ($goal->target_amount ?? 0);
                $currentAmount = (float) ($goal->current_amount ?? 0);

                // Convert amounts to user's display currency
                $convertedTarget = CurrencyHelper::convertStoredAmount($targetAmount, $userCurrency);
                $convertedCurrent = CurrencyHelper::convertStoredAmount($currentAmount, $userCurrency);

                // Calculate days remaining using PHP to match original logic
                $deadline = Carbon::createFromFormat('Y-m-d', $goal->deadline);
                $daysRemaining = (int) now()->diffInDays($deadline, false);

                return [
                    'id' => $goal->id,
                    'title' => $goal->title ?? 'Sin título',
                    'target_amount' => $convertedTarget,
                    'current_amount' => $convertedCurrent,
                    'deadline' => $goal->deadline,
                    'progress_percentage' => $convertedTarget > 0 ? ($convertedCurrent / $convertedTarget) * 100 : 0,
                    'days_remaining' => $daysRemaining,
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get optimized debt data with single query
     */
    public static function getDebtData(User $user): array
    {
        try {
            $results = DB::table('debts as d')
                ->selectRaw('
                    d.id,
                    d.name,
                    d.amount,
                    d.minimum_payment,
                    d.due_date,
                    d.status,
                    DATEDIFF(d.due_date, CURDATE()) as days_until_due,
                    COALESCE(SUM(dp.amount), 0) as total_paid
                ')
                ->leftJoin('debt_payments as dp', 'd.id', '=', 'dp.debt_id')
                ->where('d.user_id', $user->id)
                ->where('d.status', 'active')
                ->groupBy('d.id', 'd.name', 'd.amount', 'd.minimum_payment', 'd.due_date', 'd.status')
                ->orderBy('d.due_date', 'asc')
                ->get();

            if ($results->isEmpty()) {
                return [];
            }

            // Get user's display currency - use user parameter directly
            $userCurrency = $user->display_currency ?? config('currencies.default', 'USD');

            return $results->map(function ($debt) use ($userCurrency) {
                $originalAmount = (float) ($debt->amount ?? 0);
                $totalPaid = (float) ($debt->total_paid ?? 0);
                $remainingBalance = $originalAmount - $totalPaid;

                // Convert amounts to user's display currency
                $convertedBalance = CurrencyHelper::convertStoredAmount($originalAmount, $userCurrency);
                $convertedRemaining = CurrencyHelper::convertStoredAmount(max(0, $remainingBalance), $userCurrency);
                $convertedMinPayment = CurrencyHelper::convertStoredAmount((float) ($debt->minimum_payment ?? 0), $userCurrency);

                return [
                    'id' => $debt->id,
                    'name' => $debt->name ?? 'Sin nombre',
                    'amount' => $convertedBalance,
                    'balance' => $convertedBalance,
                    'remaining_balance' => $convertedRemaining,
                    'minimum_payment' => $convertedMinPayment,
                    'due_date' => $debt->due_date,
                    'days_until_due' => (int) ($debt->days_until_due ?? 0),
                    'total_paid' => CurrencyHelper::convertStoredAmount($totalPaid, $userCurrency),
                    'payment_progress' => $originalAmount > 0 ? ($totalPaid / $originalAmount) * 100 : 0,
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get optimized recent transactions with single query
     */
    public static function getRecentTransactions(User $user, int $limit = 5): array
    {
        try {
            $results = DB::table('transactions')
                ->select('id', 'type', 'category', 'amount', 'date', 'note')
                ->where('user_id', $user->id)
                ->orderBy('date', 'desc')
                ->limit($limit)
                ->get();

            if ($results->isEmpty()) {
                return [];
            }

            // Get user's display currency - use user parameter directly
            $userCurrency = $user->display_currency ?? config('currencies.default', 'USD');

            return $results->map(function ($transaction) use ($userCurrency) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type ?? 'expense',
                    'category' => $transaction->category ?? 'Sin categoría',
                    'amount' => CurrencyHelper::convertStoredAmount((float) ($transaction->amount ?? 0), $userCurrency),
                    'date' => $transaction->date,
                    'note' => $transaction->note ?? '',
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get optimized monthly spending data for charts
     */
    public static function getMonthlySpendingData(User $user, int $months = 6): array
    {
        $data = [];
        $labels = [];
        $incomeData = [];
        $expenseData = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M Y');
            $labels[] = $monthName;

            $summary = self::getMonthlySummary($user, $date->month, $date->year);
            $incomeData[] = $summary['income'];
            $expenseData[] = $summary['expenses'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Income',
                    'data' => $incomeData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Expenses',
                    'data' => $expenseData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'tension' => 0.1,
                ],
            ],
        ];
    }

    /**
     * Get optimized category spending data
     */
    public static function getCategorySpendingData(User $user, int $month = null, int $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        $categories = DB::table('transactions')
            ->selectRaw('category, SUM(amount) as total_amount')
            ->where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->get();

        return [
            'labels' => $categories->pluck('category')->toArray(),
            'datasets' => [
                [
                    'label' => 'Spending by Category',
                    'data' => $categories->pluck('total_amount')->map(fn($amount) => (float) $amount)->toArray(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                    ],
                ],
            ],
        ];
    }
}