<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Goal;
use App\Models\Transaction;
use App\Services\GoalTrackingService;
use App\Notifications\GoalReachedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GoalTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $goalTrackingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->goalTrackingService = new GoalTrackingService();
    }

    public function test_income_savings_transaction_updates_goal_progress()
    {
        // Create a goal
        $goal = Goal::create([
            'user_id' => $this->user->id,
            'title' => 'Vacation Fund',
            'target_amount' => 1000.00,
            'current_amount' => 0.00,
            'deadline' => now()->addMonths(6)
        ]);

        // Create a savings transaction (this will automatically trigger GoalTrackingService)
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'category' => 'savings',
            'amount' => 500.00,
            'date' => now()->format('Y-m-d'),
            'note' => 'Monthly savings'
        ]);

        // Check if goal progress was updated
        $updatedGoal = Goal::find($goal->id);
        $this->assertEquals(500.00, $updatedGoal->current_amount);
    }

    public function test_savings_transaction_distributes_across_multiple_goals()
    {
        // Create two goals
        $goal1 = Goal::create([
            'user_id' => $this->user->id,
            'title' => 'Emergency Fund',
            'target_amount' => 1000.00,
            'current_amount' => 0.00,
            'deadline' => now()->addMonths(3)
        ]);

        $goal2 = Goal::create([
            'user_id' => $this->user->id,
            'title' => 'Vacation Fund',
            'target_amount' => 2000.00,
            'current_amount' => 0.00,
            'deadline' => now()->addMonths(6)
        ]);

        // Create a large savings transaction (this will automatically trigger GoalTrackingService)
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'category' => 'savings',
            'amount' => 1500.00,
            'date' => now()->format('Y-m-d'),
            'note' => 'Large savings deposit'
        ]);

        // Check if goals were updated correctly (goal1 should be fully funded, goal2 partially)
        $updatedGoal1 = Goal::find($goal1->id);
        $updatedGoal2 = Goal::find($goal2->id);

        $this->assertEquals(1000.00, $updatedGoal1->current_amount); // Fully funded
        $this->assertEquals(500.00, $updatedGoal2->current_amount); // Partially funded
    }

    public function test_goal_reached_notification_is_sent()
    {
        Notification::fake();

        // Create a goal
        $goal = Goal::create([
            'user_id' => $this->user->id,
            'title' => 'Small Goal',
            'target_amount' => 100.00,
            'current_amount' => 0.00,
            'deadline' => now()->addMonths(1)
        ]);

        // Create a savings transaction that will reach the goal (this will automatically trigger GoalTrackingService)
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'category' => 'savings',
            'amount' => 100.00,
            'date' => now()->format('Y-m-d'),
            'note' => 'Goal completion'
        ]);

        // Check if notification was sent
        Notification::assertSentTo($this->user, GoalReachedNotification::class);
    }

    public function test_non_savings_transaction_does_not_update_goals()
    {
        // Create a goal
        $goal = Goal::create([
            'user_id' => $this->user->id,
            'title' => 'Vacation Fund',
            'target_amount' => 1000.00,
            'current_amount' => 0.00,
            'deadline' => now()->addMonths(6)
        ]);

        // Create a non-savings transaction (this will automatically trigger GoalTrackingService)
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'category' => 'salary',
            'amount' => 500.00,
            'date' => now()->format('Y-m-d'),
            'note' => 'Regular salary'
        ]);

        // Check that goal progress was not updated
        $updatedGoal = Goal::find($goal->id);
        $this->assertEquals(0.00, $updatedGoal->current_amount);
    }

    public function test_expense_transaction_does_not_update_goals()
    {
        // Create a goal
        $goal = Goal::create([
            'user_id' => $this->user->id,
            'title' => 'Vacation Fund',
            'target_amount' => 1000.00,
            'current_amount' => 0.00,
            'deadline' => now()->addMonths(6)
        ]);

        // Create an expense transaction (this will automatically trigger GoalTrackingService)
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'type' => 'expense',
            'category' => 'groceries',
            'amount' => 100.00,
            'date' => now()->format('Y-m-d'),
            'note' => 'Weekly groceries'
        ]);

        // Check that goal progress was not updated
        $updatedGoal = Goal::find($goal->id);
        $this->assertEquals(0.00, $updatedGoal->current_amount);
    }

    public function test_manual_goal_progress_update()
    {
        // Create a goal
        $goal = Goal::create([
            'user_id' => $this->user->id,
            'title' => 'Vacation Fund',
            'target_amount' => 1000.00,
            'current_amount' => 0.00,
            'deadline' => now()->addMonths(6)
        ]);

        // Update goal progress manually
        $result = $this->goalTrackingService->updateGoalProgress($goal, 500.00);

        // Check that goal was updated
        $updatedGoal = Goal::find($goal->id);
        $this->assertEquals(500.00, $updatedGoal->current_amount);
        $this->assertFalse($result); // Goal not reached yet
    }

    public function test_manual_goal_progress_update_reaches_goal()
    {
        Notification::fake();

        // Create a goal
        $goal = Goal::create([
            'user_id' => $this->user->id,
            'title' => 'Small Goal',
            'target_amount' => 100.00,
            'current_amount' => 0.00,
            'deadline' => now()->addMonths(1)
        ]);

        // Update goal progress manually to reach the goal
        $result = $this->goalTrackingService->updateGoalProgress($goal, 100.00);

        // Check that goal was reached
        $updatedGoal = Goal::find($goal->id);
        $this->assertEquals(100.00, $updatedGoal->current_amount);
        $this->assertTrue($result); // Goal reached

        // Check if notification was sent
        Notification::assertSentTo($this->user, GoalReachedNotification::class);
    }

    public function test_goals_are_prioritized_by_deadline()
    {
        // Create two goals with different deadlines
        $goal1 = Goal::create([
            'user_id' => $this->user->id,
            'title' => 'Long-term Goal',
            'target_amount' => 1000.00,
            'current_amount' => 0.00,
            'deadline' => now()->addMonths(12)
        ]);

        $goal2 = Goal::create([
            'user_id' => $this->user->id,
            'title' => 'Short-term Goal',
            'target_amount' => 500.00,
            'current_amount' => 0.00,
            'deadline' => now()->addMonths(1)
        ]);

        // Create a savings transaction (this will automatically trigger GoalTrackingService)
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'category' => 'savings',
            'amount' => 300.00,
            'date' => now()->format('Y-m-d'),
            'note' => 'Monthly savings'
        ]);

        // Check that the short-term goal (earlier deadline) was prioritized
        $updatedGoal1 = Goal::find($goal1->id);
        $updatedGoal2 = Goal::find($goal2->id);

        $this->assertEquals(0.00, $updatedGoal1->current_amount); // No progress (later deadline)
        $this->assertEquals(300.00, $updatedGoal2->current_amount); // Progress (earlier deadline)
    }
} 