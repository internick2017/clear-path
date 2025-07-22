<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Budget;
use App\Models\Transaction;
use App\Models\Goal;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_dashboard()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Dashboard'));
    }

    public function test_dashboard_shows_monthly_summary()
    {
        $this->actingAs($this->user);
        
        // Create some transactions for current month
        Transaction::factory()->for($this->user)->create([
            'type' => 'income',
            'amount' => 5000.00,
            'date' => now()->format('Y-m-d')
        ]);
        
        Transaction::factory()->for($this->user)->create([
            'type' => 'expense',
            'amount' => 2000.00,
            'date' => now()->format('Y-m-d')
        ]);
        
        $response = $this->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('monthlySummary')
                ->where('monthlySummary.income', 5000)
                ->where('monthlySummary.expenses', 2000)
                ->where('monthlySummary.net', 3000)
        );
    }

    public function test_dashboard_shows_budgets_data()
    {
        $this->actingAs($this->user);
        
        $budget = Budget::factory()->for($this->user)->create([
            'category' => 'Groceries',
            'limit' => 500.00,
            'spent' => 300.00,
            'month' => now()->format('Y-m-01')
        ]);
        
        $response = $this->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('budgets')
                ->where('budgets.0.category', 'Groceries')
                ->where('budgets.0.limit', 500)
                ->where('budgets.0.spent', 300)
                ->where('budgets.0.remaining', 200)
                ->where('budgets.0.percentage', 60)
                ->where('budgets.0.is_exceeded', false)
        );
    }

    public function test_dashboard_shows_active_goals()
    {
        $this->actingAs($this->user);
        
        $goal = Goal::factory()->for($this->user)->create([
            'title' => 'Vacation Fund',
            'target_amount' => 5000.00,
            'current_amount' => 2000.00,
            'deadline' => now()->addMonths(6)->format('Y-m-d')
        ]);
        
        $response = $this->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('activeGoals')
                ->where('activeGoals.0.title', 'Vacation Fund')
                ->where('activeGoals.0.target_amount', 5000)
                ->where('activeGoals.0.current_amount', 2000)
                ->where('activeGoals.0.progress_percentage', 40)
        );
    }

    public function test_dashboard_shows_active_debts()
    {
        $this->actingAs($this->user);
        
        $debt = Debt::factory()->for($this->user)->create([
            'name' => 'Credit Card',
            'amount' => 10000.00,
            'status' => 'active',
            'interest_rate' => 15.00
        ]);
        
        // Add a payment to calculate progress
        $debt->addPayment(2000.00, now()->format('Y-m-d'));
        
        $response = $this->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('activeDebts')
                ->where('activeDebts.0.name', 'Credit Card')
                ->where('activeDebts.0.amount', 10000)
                ->where('activeDebts.0.remaining_balance', 8000)
                ->where('activeDebts.0.payment_progress', 20)
        );
    }

    public function test_dashboard_shows_recent_transactions()
    {
        $this->actingAs($this->user);
        
        $transaction = Transaction::factory()->for($this->user)->create([
            'type' => 'expense',
            'category' => 'Dining',
            'amount' => 50.00,
            'date' => now()->format('Y-m-d'),
            'note' => 'Lunch'
        ]);
        
        $response = $this->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('recentTransactions')
                ->where('recentTransactions.0.type', 'expense')
                ->where('recentTransactions.0.category', 'Dining')
                ->where('recentTransactions.0.amount', 50)
                ->where('recentTransactions.0.note', 'Lunch')
        );
    }

    public function test_dashboard_shows_top_expense_categories()
    {
        $this->actingAs($this->user);
        
        // Create multiple transactions in the same category
        Transaction::factory()->for($this->user)->create([
            'type' => 'expense',
            'category' => 'Groceries',
            'amount' => 100.00,
            'date' => now()->format('Y-m-d')
        ]);
        
        Transaction::factory()->for($this->user)->create([
            'type' => 'expense',
            'category' => 'Groceries',
            'amount' => 150.00,
            'date' => now()->format('Y-m-d')
        ]);
        
        Transaction::factory()->for($this->user)->create([
            'type' => 'expense',
            'category' => 'Dining',
            'amount' => 50.00,
            'date' => now()->format('Y-m-d')
        ]);
        
        $response = $this->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('topExpenseCategories')
                ->where('topExpenseCategories.0.category', 'Groceries')
                ->where('topExpenseCategories.0.total', 250)
        );
    }

    public function test_dashboard_shows_chart_data()
    {
        $this->actingAs($this->user);
        
        // Create some transactions for chart data
        Transaction::factory()->for($this->user)->create([
            'type' => 'income',
            'amount' => 1000.00,
            'date' => now()->format('Y-m-d')
        ]);
        
        Transaction::factory()->for($this->user)->create([
            'type' => 'expense',
            'amount' => 500.00,
            'date' => now()->format('Y-m-d')
        ]);
        
        $response = $this->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('chartData')
                ->has('chartData.monthlySpending')
                ->has('chartData.budgetVsActual')
        );
    }

    public function test_dashboard_shows_goal_progress_chart_when_goals_exist()
    {
        $this->actingAs($this->user);
        
        $goal = Goal::factory()->for($this->user)->create([
            'title' => 'Emergency Fund',
            'target_amount' => 10000.00,
            'current_amount' => 5000.00,
            'deadline' => now()->addMonths(12)->format('Y-m-d')
        ]);
        
        $response = $this->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('chartData.goalProgress')
        );
    }

    public function test_dashboard_handles_empty_data_gracefully()
    {
        $this->actingAs($this->user);
        
        // Create some sample data first to test the empty data scenario
        // The dashboard will show this data, so we test that it handles data properly
        $response = $this->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('budgets')
                ->has('activeGoals')
                ->has('activeDebts')
                ->has('recentTransactions')
                ->has('topExpenseCategories')
                ->has('monthlySummary')
                ->has('chartData')
        );
    }

    public function test_dashboard_calculates_budget_percentage_correctly()
    {
        $this->actingAs($this->user);
        
        // Create a budget that is exceeded
        $budget = Budget::factory()->for($this->user)->create([
            'category' => 'TestCategory',
            'limit' => 200.00,
            'spent' => 250.00,
            'month' => now()->format('Y-m-01')
        ]);
        
        $response = $this->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('budgets')
        );
        
        // Get the response data and check the budget
        $responseData = $response->viewData('page')['props'];
        $testBudget = collect($responseData['budgets'])->firstWhere('category', 'TestCategory');
        
        $this->assertNotNull($testBudget);
        $this->assertEquals(125.0, $testBudget['percentage']);
        $this->assertTrue($testBudget['is_exceeded']);
    }

    public function test_dashboard_shows_correct_days_remaining_for_goals()
    {
        $this->actingAs($this->user);
        
        $goal = Goal::factory()->for($this->user)->create([
            'title' => 'Short Term Goal',
            'target_amount' => 1000.00,
            'current_amount' => 500.00,
            'deadline' => now()->addDays(30)->format('Y-m-d')
        ]);
        
        $response = $this->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->where('activeGoals.0.days_remaining', 29)
        );
    }
} 