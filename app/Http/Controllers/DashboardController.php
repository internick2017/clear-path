<?php

namespace App\Http\Controllers;

use App\Services\CacheService;
use App\Services\QueryOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Sample data creation removed - data should be created via seeders
        // if ($user->transactions()->count() === 0 && !app()->environment('testing')) {
        //     $this->createSampleData($user);
        // }

        // Get cached dashboard data
        $dashboardData = CacheService::getDashboardData($user);

        // Chart data for monthly spending (optimized)
        $monthlySpendingData = QueryOptimizationService::getMonthlySpendingData($user);

        // Chart data for budget vs actual
        $budgetVsActualData = $this->getBudgetVsActualData($dashboardData['budgets']);

        // Chart data for goal progress
        $goalProgressData = $this->getGoalProgressData($dashboardData['activeGoals']);

        // NEW: Chart data for fixed vs variable expenses
        $fixedVsVariableData = $this->getFixedVsVariableData($user);

        // NEW: Financial health score
        $financialHealthScore = $this->calculateFinancialHealthScore($user, $dashboardData);

        // Debug logging
        \Log::info('Dashboard data summary:', [
            'user_id' => $user->id,
            'monthly_income' => $dashboardData['monthlySummary']['income'],
            'monthly_expenses' => $dashboardData['monthlySummary']['expenses'],
            'budgets_count' => count($dashboardData['budgets']),
            'active_goals_count' => count($dashboardData['activeGoals']),
            'transactions_count' => $user->transactions()->count(),
            'monthly_spending_labels' => $monthlySpendingData['labels'] ?? [],
            'budget_vs_actual_labels' => $budgetVsActualData['labels'] ?? [],
        ]);

        return Inertia::render('Dashboard', [
            'budgets' => $dashboardData['budgets'],
            'monthlySummary' => $dashboardData['monthlySummary'],
            'topExpenseCategories' => $dashboardData['topExpenseCategories'],
            'activeGoals' => $dashboardData['activeGoals'],
            'activeDebts' => $dashboardData['activeDebts'],
            'recentTransactions' => $dashboardData['recentTransactions'],
            'financialHealthScore' => $financialHealthScore,
            'chartData' => [
                'monthlySpending' => $monthlySpendingData,
                'budgetVsActual' => $budgetVsActualData,
                'goalProgress' => $goalProgressData,
                'fixedVsVariable' => $fixedVsVariableData,
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

        // Handle empty budgets
        if (empty($budgets)) {
            return [
                'labels' => ['Sin presupuestos'],
                'datasets' => [
                    [
                        'label' => 'Budget Limit',
                        'data' => [0],
                        'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                        'borderColor' => 'rgb(59, 130, 246)',
                        'borderWidth' => 1,
                    ],
                    [
                        'label' => 'Actual Spent',
                        'data' => [0],
                        'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                        'borderColor' => 'rgb(239, 68, 68)',
                        'borderWidth' => 1,
                    ],
                ],
            ];
        }

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

        // Handle empty goals
        if (empty($goals)) {
            return [
                'labels' => ['Sin metas'],
                'datasets' => [
                    [
                        'label' => 'Progress %',
                        'data' => [0],
                        'backgroundColor' => ['rgba(200, 200, 200, 0.8)'],
                        'borderWidth' => 1,
                    ],
                ],
            ];
        }

        foreach ($goals as $index => $goal) {
            $goalNames[] = $goal['title'];
            $progressData[] = $goal['progress_percentage'] ?? 0;
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

    private function getFixedVsVariableData($user)
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $fixedExpenses = $user->transactions()
            ->where('type', 'expense')
            ->where('expense_type', 'fixed')
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');

        $variableExpenses = $user->transactions()
            ->where('type', 'expense')
            ->where('expense_type', 'variable')
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');

        return [
            'labels' => ['Gastos Fijos', 'Gastos Variables'],
            'datasets' => [
                [
                    'label' => 'Tipo de Gasto',
                    'data' => [(float) $fixedExpenses, (float) $variableExpenses],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)', // Blue for fixed
                        'rgba(139, 92, 246, 0.8)', // Purple for variable
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(139, 92, 246)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    private function calculateFinancialHealthScore($user, $dashboardData)
    {
        $score = 0;
        $maxScore = 100;

        // Handle empty data
        if (empty($dashboardData)) {
            return 0;
        }

        // Income vs Expenses ratio (30 points)
        $monthlySummary = $dashboardData['monthlySummary'] ?? ['income' => 0, 'expenses' => 0, 'net' => 0];
        if ($monthlySummary['income'] > 0) {
            $expenseRatio = $monthlySummary['expenses'] / $monthlySummary['income'];
            if ($expenseRatio <= 0.5) $score += 30; // Excellent (<50% of income)
            elseif ($expenseRatio <= 0.7) $score += 20; // Good (50-70%)
            elseif ($expenseRatio <= 0.9) $score += 10; // Fair (70-90%)
            // Poor (>90%) gets 0 points
        }

        // Budget compliance (25 points)
        $budgets = $dashboardData['budgets'] ?? [];
        if (count($budgets) > 0) {
            $compliantBudgets = count(array_filter($budgets, fn($b) => !($b['is_exceeded'] ?? false)));
            $complianceRate = $compliantBudgets / count($budgets);
            $score += (int) ($complianceRate * 25);
        }

        // Savings rate (20 points)
        if ($monthlySummary['income'] > 0) {
            $savingsRate = $monthlySummary['net'] / $monthlySummary['income'];
            if ($savingsRate >= 0.2) $score += 20; // Excellent (≥20%)
            elseif ($savingsRate >= 0.1) $score += 15; // Good (10-20%)
            elseif ($savingsRate >= 0.05) $score += 10; // Fair (5-10%)
            elseif ($savingsRate >= 0) $score += 5; // Poor but positive
        }

        // Debt management (15 points)
        $activeDebts = $dashboardData['activeDebts'] ?? [];
        if (count($activeDebts) === 0) {
            $score += 15; // No debt = excellent
        } else {
            $totalDebt = array_sum(array_column($activeDebts, 'balance'));
            $monthlyIncome = $monthlySummary['income'];
            $debtToIncomeRatio = $monthlyIncome > 0 ? $totalDebt / ($monthlyIncome * 12) : 1;

            if ($debtToIncomeRatio <= 0.5) $score += 12; // Good (≤6 months of income)
            elseif ($debtToIncomeRatio <= 1) $score += 8; // Fair (6-12 months)
            elseif ($debtToIncomeRatio <= 2) $score += 4; // Poor (1-2 years)
        }

        // Goal progress (10 points)
        $activeGoals = $dashboardData['activeGoals'] ?? [];
        if (count($activeGoals) > 0) {
            $avgProgress = array_sum(array_column($activeGoals, 'progress_percentage')) / count($activeGoals);
            $score += (int) ($avgProgress * 0.1); // Up to 10 points based on average progress
        }

        return [
            'score' => min($score, $maxScore),
            'percentage' => min($score, $maxScore),
            'grade' => $this->getHealthGrade($score),
            'breakdown' => [
                'income_expense_ratio' => $monthlySummary['income'] > 0 ? ($monthlySummary['expenses'] / $monthlySummary['income']) * 100 : 100,
                'budget_compliance' => count($budgets) > 0 ? (count(array_filter($budgets, fn($b) => !($b['is_exceeded'] ?? false))) / count($budgets)) * 100 : 0,
                'savings_rate' => $monthlySummary['income'] > 0 ? ($monthlySummary['net'] / $monthlySummary['income']) * 100 : 0,
                'debt_ratio' => count($activeDebts) > 0 && $monthlySummary['income'] > 0 ?
                    (array_sum(array_column($activeDebts, 'balance')) / ($monthlySummary['income'] * 12)) * 100 : 0,
                'goal_progress' => count($activeGoals) > 0 ?
                    array_sum(array_column($activeGoals, 'progress_percentage')) / count($activeGoals) : 0,
            ]
        ];
    }

    private function getHealthGrade($score)
    {
        if ($score >= 90) return ['grade' => 'A', 'label' => 'Excelente', 'color' => 'green'];
        if ($score >= 80) return ['grade' => 'B', 'label' => 'Muy Bueno', 'color' => 'blue'];
        if ($score >= 70) return ['grade' => 'C', 'label' => 'Bueno', 'color' => 'yellow'];
        if ($score >= 60) return ['grade' => 'D', 'label' => 'Regular', 'color' => 'orange'];
        return ['grade' => 'F', 'label' => 'Necesita Mejora', 'color' => 'red'];
    }
}