<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Budget;
use App\Services\BudgetService;
use App\Notifications\BudgetExceededNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $budgetService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->budgetService = new BudgetService();
        $this->user = User::factory()->create();
    }

    public function test_check_budget_limit_returns_false_when_no_budget_exists()
    {
        $result = $this->budgetService->checkBudgetLimit($this->user, 'Groceries', 100.00);
        
        $this->assertFalse($result);
    }

    public function test_check_budget_limit_updates_spent_amount()
    {
        $budget = Budget::factory()->for($this->user)->create([
            'category' => 'Groceries',
            'limit' => 500.00,
            'spent' => 200.00,
            'month' => now()->format('Y-m-d')
        ]);

        $this->budgetService->checkBudgetLimit($this->user, 'Groceries', 100.00);

        $budget->refresh();
        $this->assertEquals(300.00, $budget->spent);
    }

    public function test_check_budget_limit_sends_notification_when_exceeded()
    {
        Notification::fake();

        $budget = Budget::factory()->for($this->user)->create([
            'category' => 'Groceries',
            'limit' => 500.00,
            'spent' => 400.00,
            'month' => now()->format('Y-m-d')
        ]);

        $result = $this->budgetService->checkBudgetLimit($this->user, 'Groceries', 100.00);

        $this->assertTrue($result);
        Notification::assertSentTo($this->user, BudgetExceededNotification::class);
    }

    public function test_create_budget_creates_new_budget()
    {
        $budget = $this->budgetService->createBudget($this->user, 'Groceries', 500.00);

        $this->assertInstanceOf(Budget::class, $budget);
        $this->assertEquals($this->user->id, $budget->user_id);
        $this->assertEquals('Groceries', $budget->category);
        $this->assertEquals(500.00, $budget->limit);
        $this->assertEquals(0, $budget->spent);
    }

    public function test_reset_monthly_budgets_updates_old_budgets()
    {
        // Create budget for previous month
        $oldBudget = Budget::factory()->for($this->user)->create([
            'month' => now()->subMonth()->format('Y-m-d'),
            'spent' => 200.00
        ]);

        // Create budget for current month
        $currentBudget = Budget::factory()->for($this->user)->create([
            'month' => now()->format('Y-m-d'),
            'spent' => 100.00
        ]);

        $this->budgetService->resetMonthlyBudgets();

        $oldBudget->refresh();
        $currentBudget->refresh();

        // Old budget should be reset and updated to current month
        $this->assertEquals(0, $oldBudget->spent);
        $this->assertEquals(now()->format('Y-m'), $oldBudget->month);
        
        // Current budget should remain unchanged
        $this->assertEquals(100.00, $currentBudget->spent);
    }
} 