<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Services\QueryOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class QueryOptimizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_gets_monthly_summary_with_single_query()
    {
        // Create test transactions
        Transaction::factory()->for($this->user)->create([
            'type' => 'income',
            'amount' => 1000,
            'date' => now()->format('Y-m-d'),
        ]);

        Transaction::factory()->for($this->user)->create([
            'type' => 'expense',
            'amount' => 300,
            'date' => now()->format('Y-m-d'),
        ]);

        $summary = QueryOptimizationService::getMonthlySummary($this->user);

        $this->assertIsArray($summary);
        $this->assertEquals(1000, $summary['income']);
        $this->assertEquals(300, $summary['expenses']);
        $this->assertEquals(700, $summary['net']);
        $this->assertEquals(2, $summary['total_transactions']);
    }

    /** @test */
    public function it_gets_top_expense_categories()
    {
        // Create transactions in different categories
        Transaction::factory()->for($this->user)->create([
            'type' => 'expense',
            'category' => 'Groceries',
            'amount' => 500,
            'date' => now()->format('Y-m-d'),
        ]);

        Transaction::factory()->for($this->user)->create([
            'type' => 'expense',
            'category' => 'Entertainment',
            'amount' => 200,
            'date' => now()->format('Y-m-d'),
        ]);

        $categories = QueryOptimizationService::getTopExpenseCategories($this->user);

        $this->assertIsArray($categories);
        $this->assertCount(2, $categories);
        $this->assertEquals('Groceries', $categories[0]['category']);
        $this->assertEquals(500, $categories[0]['total']);
        $this->assertEquals('Entertainment', $categories[1]['category']);
        $this->assertEquals(200, $categories[1]['total']);
    }

    /** @test */
    public function it_gets_budget_data_with_actual_spending()
    {
        // Create budget
        Budget::factory()->for($this->user)->create([
            'category' => 'Groceries',
            'limit' => 500,
            'spent' => 0,
            'month' => now()->format('Y-m-01'),
        ]);

        // Create transactions in same category
        Transaction::factory()->for($this->user)->create([
            'type' => 'expense',
            'category' => 'Groceries',
            'amount' => 300,
            'date' => now()->format('Y-m-d'),
        ]);

        $budgets = QueryOptimizationService::getBudgetData($this->user);

        $this->assertIsArray($budgets);
        $this->assertCount(1, $budgets);
        $this->assertEquals('Groceries', $budgets[0]['category']);
        $this->assertEquals(500, $budgets[0]['limit']);
        $this->assertEquals(300, $budgets[0]['spent']); // Should use actual spending
        $this->assertEquals(200, $budgets[0]['remaining']);
        $this->assertEquals(60, $budgets[0]['percentage']);
        $this->assertFalse($budgets[0]['is_exceeded']);
    }

    /** @test */
    public function it_gets_goal_data_correctly()
    {
        // Create active goal
        Goal::factory()->for($this->user)->create([
            'title' => 'Emergency Fund',
            'target_amount' => 1000,
            'current_amount' => 300,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ]);

        // Create completed goal (should not appear)
        Goal::factory()->for($this->user)->create([
            'title' => 'Completed Goal',
            'target_amount' => 500,
            'current_amount' => 500,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $goals = QueryOptimizationService::getGoalData($this->user);

        $this->assertIsArray($goals);
        $this->assertCount(1, $goals); // Only active goal should appear
        $this->assertEquals('Emergency Fund', $goals[0]['title']);
        $this->assertEquals(1000, $goals[0]['target_amount']);
        $this->assertEquals(300, $goals[0]['current_amount']);
        $this->assertEquals(30, $goals[0]['progress_percentage']);
        $this->assertEquals(30, $goals[0]['days_remaining']);
    }

    /** @test */
    public function it_gets_debt_data_with_payments()
    {
        // Create debt
        $debt = Debt::factory()->for($this->user)->create([
            'name' => 'Credit Card',
            'amount' => 1000,
            'minimum_payment' => 50,
            'status' => 'active',
            'due_date' => now()->addDays(15)->format('Y-m-d'),
        ]);

        // Create payment
        DebtPayment::factory()->for($debt)->for($this->user)->create([
            'amount' => 200,
            'payment_date' => now()->format('Y-m-d'),
        ]);

        $debts = QueryOptimizationService::getDebtData($this->user);

        $this->assertIsArray($debts);
        $this->assertCount(1, $debts);
        $this->assertEquals('Credit Card', $debts[0]['name']);
        $this->assertEquals(1000, $debts[0]['amount']);
        $this->assertEquals(1000, $debts[0]['balance']);
        $this->assertEquals(800, $debts[0]['remaining_balance']); // 1000 - 200
        $this->assertEquals(50, $debts[0]['minimum_payment']);
        $this->assertEquals(15, $debts[0]['days_until_due']);
        $this->assertEquals(200, $debts[0]['total_paid']);
        $this->assertEquals(20, $debts[0]['payment_progress']); // 200/1000 * 100
    }

    /** @test */
    public function it_gets_recent_transactions()
    {
        // Create transactions with different dates
        Transaction::factory()->for($this->user)->create([
            'amount' => 100,
            'date' => now()->subDays(1)->format('Y-m-d'),
        ]);

        Transaction::factory()->for($this->user)->create([
            'amount' => 200,
            'date' => now()->format('Y-m-d'),
        ]);

        $transactions = QueryOptimizationService::getRecentTransactions($this->user, 5);

        $this->assertIsArray($transactions);
        $this->assertCount(2, $transactions);
        // Should be ordered by date desc
        $this->assertEquals(200, $transactions[0]['amount']); // Today's transaction first
        $this->assertEquals(100, $transactions[1]['amount']); // Yesterday's transaction second
    }

    /** @test */
    public function it_gets_monthly_spending_data_for_charts()
    {
        // Create transactions for different months
        Transaction::factory()->for($this->user)->create([
            'type' => 'income',
            'amount' => 1000,
            'date' => now()->format('Y-m-d'),
        ]);

        Transaction::factory()->for($this->user)->create([
            'type' => 'expense',
            'amount' => 300,
            'date' => now()->format('Y-m-d'),
        ]);

        $chartData = QueryOptimizationService::getMonthlySpendingData($this->user, 6);

        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('datasets', $chartData);
        $this->assertCount(6, $chartData['labels']); // 6 months of labels
        $this->assertCount(2, $chartData['datasets']); // Income and Expenses datasets

        // Check that current month has data
        $currentMonthIndex = 5; // Last month in 6-month array
        $this->assertEquals(1000, $chartData['datasets'][0]['data'][$currentMonthIndex]); // Income
        $this->assertEquals(300, $chartData['datasets'][1]['data'][$currentMonthIndex]); // Expenses
    }

    /** @test */
    public function it_handles_empty_data_gracefully()
    {
        // Test with no data
        $summary = QueryOptimizationService::getMonthlySummary($this->user);
        $this->assertEquals(0, $summary['income']);
        $this->assertEquals(0, $summary['expenses']);
        $this->assertEquals(0, $summary['net']);

        $categories = QueryOptimizationService::getTopExpenseCategories($this->user);
        $this->assertEmpty($categories);

        $budgets = QueryOptimizationService::getBudgetData($this->user);
        $this->assertEmpty($budgets);

        $goals = QueryOptimizationService::getGoalData($this->user);
        $this->assertEmpty($goals);

        $debts = QueryOptimizationService::getDebtData($this->user);
        $this->assertEmpty($debts);

        $transactions = QueryOptimizationService::getRecentTransactions($this->user);
        $this->assertEmpty($transactions);
    }

    /** @test */
    public function it_respects_month_and_year_parameters()
    {
        // Create transactions for different months
        Transaction::factory()->for($this->user)->create([
            'type' => 'income',
            'amount' => 1000,
            'date' => '2025-01-15',
        ]);

        Transaction::factory()->for($this->user)->create([
            'type' => 'income',
            'amount' => 2000,
            'date' => '2025-02-15',
        ]);

        // Test January
        $januarySummary = QueryOptimizationService::getMonthlySummary($this->user, 1, 2025);
        $this->assertEquals(1000, $januarySummary['income']);

        // Test February
        $februarySummary = QueryOptimizationService::getMonthlySummary($this->user, 2, 2025);
        $this->assertEquals(2000, $februarySummary['income']);
    }
}
