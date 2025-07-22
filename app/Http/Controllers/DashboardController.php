<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Create sample data if none exists (for testing purposes)
        // Skip sample data creation during testing to avoid interference
        if ($user->transactions()->count() === 0 && !app()->environment('testing')) {
            $this->createSampleData($user);
        }
        
        // Get current month data
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Optimize: Get all transactions for the current month in one query
        $currentMonthTransactions = $user->transactions()
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->get();
        
        // Calculate monthly summary from the loaded transactions
        $monthlyIncome = (float) $currentMonthTransactions
            ->where('type', 'income')
            ->sum('amount');
            
        $monthlyExpenses = (float) $currentMonthTransactions
            ->where('type', 'expense')
            ->sum('amount');
        
        $monthlyNet = $monthlyIncome - $monthlyExpenses;
        
        // Top expense categories from loaded transactions
        $topExpenseCategories = $currentMonthTransactions
            ->where('type', 'expense')
            ->groupBy('category')
            ->map(function($transactions, $category) {
                return [
                    'category' => $category,
                    'total' => (float) $transactions->sum('amount'),
                ];
            })
            ->sortByDesc('total')
            ->take(5)
            ->values();
        
        // Budget data with eager loading
        $budgets = $user->budgets()
            ->whereMonth('month', $currentMonth)
            ->whereYear('month', $currentYear)
            ->get()
            ->map(function($budget) use ($currentMonthTransactions) {
                // Use the stored spent value from the budget model
                $actualSpent = (float) $budget->spent;
                
                // Verify against actual transactions (for accuracy)
                $transactionSpent = (float) $currentMonthTransactions
                    ->where('type', 'expense')
                    ->where('category', $budget->category)
                    ->sum('amount');
                
                // Use the higher value to ensure we don't miss any spending
                $spent = max($actualSpent, $transactionSpent);
                
                return [
                    'id' => $budget->id,
                    'category' => $budget->category,
                    'limit' => (float) $budget->limit,
                    'spent' => $spent,
                    'remaining' => (float) ($budget->limit - $spent),
                    'percentage' => (float) ($budget->limit > 0 ? ($spent / $budget->limit) * 100 : 0),
                    'is_exceeded' => $spent > $budget->limit,
                ];
            });
        
        // Goals data with optimized query
        $activeGoals = $user->goals()
            ->whereRaw('CAST(current_amount AS DECIMAL(10,2)) < CAST(target_amount AS DECIMAL(10,2))')
            ->where('deadline', '>', now()->format('Y-m-d'))
            ->orderBy('deadline', 'asc')
            ->get()
            ->map(function($goal) {
                return [
                    'id' => $goal->id,
                    'title' => $goal->title,
                    'target_amount' => (float) $goal->target_amount,
                    'current_amount' => (float) $goal->current_amount,
                    'deadline' => $goal->deadline,
                    'progress_percentage' => (float) ($goal->target_amount > 0 ? ($goal->current_amount / $goal->target_amount) * 100 : 0),
                    'days_remaining' => (int) now()->diffInDays($goal->deadline, false),
                ];
            });
        
        // Debt data with eager loading of payments
        $activeDebts = $user->debts()
            ->where('status', 'active')
            ->with('payments') // Eager load payments to avoid N+1 queries
            ->get()
            ->map(function($debt) {
                return [
                    'id' => $debt->id,
                    'name' => $debt->name,
                    'amount' => (float) $debt->amount,
                    'remaining_balance' => (float) $debt->getRemainingBalance(),
                    'payment_progress' => (float) $debt->payment_progress,
                    'interest_rate' => (float) $debt->interest_rate,
                ];
            });
        
        // Recent transactions (separate query for pagination)
        $recentTransactions = $user->transactions()
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'category' => $transaction->category,
                    'amount' => (float) $transaction->amount,
                    'date' => $transaction->date,
                    'note' => $transaction->note,
                ];
            });
        
        // Chart data for monthly spending
        $monthlySpendingData = $this->getMonthlySpendingData($user);
        
        // Chart data for budget vs actual
        $budgetVsActualData = $this->getBudgetVsActualData($budgets);
        
        // Chart data for goal progress
        $goalProgressData = $this->getGoalProgressData($activeGoals);
        
        // Debug logging
        \Log::info('Dashboard data summary:', [
            'user_id' => $user->id,
            'monthly_income' => $monthlyIncome,
            'monthly_expenses' => $monthlyExpenses,
            'budgets_count' => $budgets->count(),
            'active_goals_count' => $activeGoals->count(),
            'transactions_count' => $user->transactions()->count(),
            'monthly_spending_labels' => $monthlySpendingData['labels'] ?? [],
            'budget_vs_actual_labels' => $budgetVsActualData['labels'] ?? [],
        ]);
        
        return Inertia::render('Dashboard', [
            'budgets' => $budgets,
            'monthlySummary' => [
                'income' => $monthlyIncome,
                'expenses' => $monthlyExpenses,
                'net' => $monthlyNet,
            ],
            'topExpenseCategories' => $topExpenseCategories,
            'activeGoals' => $activeGoals,
            'activeDebts' => $activeDebts,
            'recentTransactions' => $recentTransactions,
            'chartData' => [
                'monthlySpending' => $monthlySpendingData,
                'budgetVsActual' => $budgetVsActualData,
                'goalProgress' => $goalProgressData,
            ],
        ]);
    }
    
    private function getMonthlySpendingData($user)
    {
        $months = [];
        $incomeData = [];
        $expenseData = [];
        
        // Get data for last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M Y');
            $months[] = $monthName;
            
            $income = $user->transactions()
                ->where('type', 'income')
                ->whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->sum('amount');
            
            $expenses = $user->transactions()
                ->where('type', 'expense')
                ->whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->sum('amount');
            
            $incomeData[] = (float) $income;
            $expenseData[] = (float) $expenses;
        }
        
        $chartData = [
            'labels' => $months,
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
        
        \Log::info('Monthly spending chart data:', $chartData);
        
        return $chartData;
    }
    
    private function getBudgetVsActualData($budgets)
    {
        $categories = [];
        $limits = [];
        $actuals = [];
        
        foreach ($budgets as $budget) {
            $categories[] = $budget['category'];
            $limits[] = (float) $budget['limit'];
            $actuals[] = (float) $budget['spent'];
        }
        
        $chartData = [
            'labels' => $categories,
            'datasets' => [
                [
                    'label' => 'Budget Limit',
                    'data' => $limits,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Actual Spent',
                    'data' => $actuals,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 1,
                ],
            ],
        ];
        
        \Log::info('Budget vs actual chart data:', $chartData);
        
        return $chartData;
    }
    
    private function getGoalProgressData($goals)
    {
        $goalNames = [];
        $progressData = [];
        $colors = [
            'rgba(59, 130, 246, 0.8)',
            'rgba(16, 185, 129, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(139, 92, 246, 0.8)',
        ];
        
        foreach ($goals as $index => $goal) {
            $goalNames[] = $goal['title'];
            $progressData[] = $goal['progress_percentage'];
        }
        
        return [
            'labels' => $goalNames,
            'datasets' => [
                [
                    'label' => 'Progress %',
                    'data' => $progressData,
                    'backgroundColor' => array_slice($colors, 0, count($progressData)),
                    'borderWidth' => 1,
                ],
            ],
        ];
    }
    
    private function createSampleData($user)
    {
        // Create sample transactions for the last 6 months
        $categories = ['Groceries', 'Entertainment', 'Transportation', 'Utilities', 'Shopping'];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            
            // Create income
            $user->transactions()->create([
                'type' => 'income',
                'category' => 'Salary',
                'amount' => rand(3000, 5000),
                'date' => $date->format('Y-m-d'),
                'note' => 'Monthly salary'
            ]);
            
            // Create expenses
            for ($j = 0; $j < 3; $j++) {
                $user->transactions()->create([
                    'type' => 'expense',
                    'category' => $categories[array_rand($categories)],
                    'amount' => rand(50, 300),
                    'date' => $date->format('Y-m-d'),
                    'note' => 'Sample expense'
                ]);
            }
        }
        
        // Create sample budgets
        foreach ($categories as $category) {
            $user->budgets()->create([
                'category' => $category,
                'limit' => rand(200, 500),
                'spent' => rand(50, 400),
                'month' => now()->format('Y-m-01')
            ]);
        }
        
        // Create sample goals
        $user->goals()->create([
            'title' => 'Emergency Fund',
            'target_amount' => 10000,
            'current_amount' => 3500,
            'deadline' => now()->addMonths(12)->format('Y-m-d')
        ]);
        
        $user->goals()->create([
            'title' => 'Vacation Fund',
            'target_amount' => 5000,
            'current_amount' => 1200,
            'deadline' => now()->addMonths(6)->format('Y-m-d')
        ]);
    }
} 