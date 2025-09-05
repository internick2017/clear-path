<?php

namespace App\Services;

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

        return [
            'income' => (float) ($summary->total_income ?? 0),
            'expenses' => (float) ($summary->total_expenses ?? 0),
            'net' => (float) (($summary->total_income ?? 0) - ($summary->total_expenses ?? 0)),
            'total_transactions' => (int) ($summary->total_transactions ?? 0),
        ];
    }

    /**
     * Get optimized top expense categories with single query
     */
    public static function getTopExpenseCategories(User $user, int $month = null, int $year = null, int $limit = 5): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        return DB::table('transactions')
            ->selectRaw('category, SUM(amount) as total_amount, COUNT(*) as transaction_count')
            ->where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category,
                    'total' => (float) $item->total_amount,
                    'transaction_count' => (int) $item->transaction_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get optimized budget data with single query
     */
    public static function getBudgetData(User $user, int $month = null, int $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        // Get budgets with actual spending in single query
        $budgets = DB::table('budgets as b')
            ->selectRaw('
                b.id,
                b.category,
                b.limit,
                b.spent as budget_spent,
                COALESCE(SUM(t.amount), 0) as actual_spent
            ')
            ->leftJoin('transactions as t', function ($join) use ($month, $year) {
                $join->on('b.category', '=', 't.category')
                    ->where('t.type', '=', 'expense')
                    ->whereMonth('t.date', $month)
                    ->whereYear('t.date', $year);
            })
            ->where('b.user_id', $user->id)
            ->whereMonth('b.month', $month)
            ->whereYear('b.month', $year)
            ->groupBy('b.id', 'b.category', 'b.limit', 'b.spent')
            ->get()
            ->map(function ($budget) {
                $actualSpent = (float) $budget->actual_spent;
                $budgetSpent = (float) $budget->budget_spent;
                $spent = max($actualSpent, $budgetSpent);
                $limit = (float) $budget->limit;

                return [
                    'id' => $budget->id,
                    'category' => $budget->category,
                    'limit' => $limit,
                    'spent' => $spent,
                    'remaining' => $limit - $spent,
                    'percentage' => $limit > 0 ? ($spent / $limit) * 100 : 0,
                    'is_exceeded' => $spent > $limit,
                ];
            })
            ->toArray();

        return $budgets;
    }

    /**
     * Get optimized goal data with single query
     */
    public static function getGoalData(User $user): array
    {
        return DB::table('goals')
            ->selectRaw('
                id,
                title,
                target_amount,
                current_amount,
                deadline,
                DATEDIFF(deadline, CURDATE()) as days_remaining
            ')
            ->where('user_id', $user->id)
            ->whereRaw('CAST(current_amount AS DECIMAL(10,2)) < CAST(target_amount AS DECIMAL(10,2))')
            ->where('deadline', '>', now()->format('Y-m-d'))
            ->orderBy('deadline', 'asc')
            ->get()
            ->map(function ($goal) {
                $targetAmount = (float) $goal->target_amount;
                $currentAmount = (float) $goal->current_amount;

                return [
                    'id' => $goal->id,
                    'title' => $goal->title,
                    'target_amount' => $targetAmount,
                    'current_amount' => $currentAmount,
                    'deadline' => $goal->deadline,
                    'progress_percentage' => $targetAmount > 0 ? ($currentAmount / $targetAmount) * 100 : 0,
                    'days_remaining' => (int) $goal->days_remaining,
                ];
            })
            ->toArray();
    }

    /**
     * Get optimized debt data with single query
     */
    public static function getDebtData(User $user): array
    {
        return DB::table('debts as d')
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
            ->get()
            ->map(function ($debt) {
                $originalAmount = (float) $debt->amount;
                $totalPaid = (float) $debt->total_paid;
                $remainingBalance = $originalAmount - $totalPaid;

                return [
                    'id' => $debt->id,
                    'name' => $debt->name,
                    'balance' => $originalAmount,
                    'remaining_balance' => $remainingBalance,
                    'minimum_payment' => (float) $debt->minimum_payment,
                    'due_date' => $debt->due_date,
                    'days_until_due' => (int) $debt->days_until_due,
                    'total_paid' => $totalPaid,
                    'payment_progress' => $originalAmount > 0 ? ($totalPaid / $originalAmount) * 100 : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get optimized recent transactions with single query
     */
    public static function getRecentTransactions(User $user, int $limit = 5): array
    {
        return DB::table('transactions')
            ->select('id', 'type', 'category', 'amount', 'date', 'note')
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'category' => $transaction->category,
                    'amount' => (float) $transaction->amount,
                    'date' => $transaction->date,
                    'note' => $transaction->note,
                ];
            })
            ->toArray();
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