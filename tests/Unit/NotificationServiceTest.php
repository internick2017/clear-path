<?php

namespace Tests\Unit;

use App\Models\Budget;
use App\Models\Debt;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = new NotificationService();
        Notification::fake();
    }

    /** @test */
    public function it_sends_budget_exceeded_notification()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'limit' => 100,
            'spent' => 150,
        ]);

        $this->notificationService->sendBudgetExceededNotification($budget);

        Notification::assertSentTo(
            $user,
            \App\Notifications\BudgetExceededNotification::class
        );
    }

    /** @test */
    public function it_sends_goal_reached_notification()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 1000,
            'current_amount' => 1000,
        ]);

        $this->notificationService->sendGoalReachedNotification($goal);

        Notification::assertSentTo(
            $user,
            \App\Notifications\GoalReachedNotification::class
        );
    }

    /** @test */
    public function it_sends_debt_payment_reminder()
    {
        $user = User::factory()->create();
        $debt = Debt::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'due_date' => Carbon::now()->addDays(5),
        ]);

        $this->notificationService->sendDebtPaymentReminder($debt);

        Notification::assertSentTo(
            $user,
            \App\Notifications\DebtPaymentReminderNotification::class
        );
    }

    /** @test */
    public function it_sends_transaction_category_change_notification()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category' => 'New Category',
        ]);

        $this->notificationService->sendTransactionCategoryChangeNotification(
            $transaction,
            'Old Category'
        );

        Notification::assertSentTo(
            $user,
            \App\Notifications\TransactionCategoryChangeNotification::class
        );
    }

    /** @test */
    public function it_sends_scheduled_debt_reminders()
    {
        $user = User::factory()->create();
        $debt1 = Debt::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'due_date' => Carbon::now()->addDays(5),
        ]);
        $debt2 = Debt::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'due_date' => Carbon::now()->addDays(3),
        ]);

        $sentCount = $this->notificationService->sendScheduledDebtReminders(7);

        $this->assertEquals(2, $sentCount);
        Notification::assertSentTo(
            $user,
            \App\Notifications\DebtPaymentReminderNotification::class,
            2
        );
    }

        /** @test */
    public function it_sends_budget_exceeded_alerts()
    {
        $user = User::factory()->create();
        $month = Carbon::now()->startOfMonth();

        $budget1 = Budget::factory()->create([
            'user_id' => $user->id,
            'category' => 'Groceries',
            'month' => $month,
            'limit' => 100,
            'spent' => 150,
        ]);
        $budget2 = Budget::factory()->create([
            'user_id' => $user->id,
            'category' => 'Dining Out',
            'month' => $month,
            'limit' => 200,
            'spent' => 250,
        ]);

        $sentCount = $this->notificationService->sendBudgetExceededAlerts($month->format('Y-m'));

        $this->assertEquals(2, $sentCount);
        Notification::assertSentTo(
            $user,
            \App\Notifications\BudgetExceededNotification::class,
            2
        );
    }

    /** @test */
    public function it_checks_and_sends_goal_reached_notifications()
    {
        $user = User::factory()->create();
        $goal1 = Goal::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 1000,
            'current_amount' => 1000,
        ]);
        $goal2 = Goal::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 500,
            'current_amount' => 600,
        ]);

        $sentCount = $this->notificationService->checkAndSendGoalReachedNotifications();

        $this->assertEquals(2, $sentCount);
        Notification::assertSentTo(
            $user,
            \App\Notifications\GoalReachedNotification::class,
            2
        );
    }

    /** @test */
    public function it_gets_user_notification_stats()
    {
        $user = User::factory()->create();

        // Create some notifications
        $user->notifications()->create([
            'id' => 'test-1',
            'type' => \App\Notifications\BudgetExceededNotification::class,
            'data' => ['type' => 'budget_exceeded'],
            'read_at' => null,
        ]);

        $user->notifications()->create([
            'id' => 'test-2',
            'type' => \App\Notifications\GoalReachedNotification::class,
            'data' => ['type' => 'goal_reached'],
            'read_at' => Carbon::now(),
        ]);

        $stats = $this->notificationService->getUserNotificationStats($user);

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['unread']);
        $this->assertCount(2, $stats['recent']);
    }

    /** @test */
    public function it_marks_all_notifications_as_read()
    {
        $user = User::factory()->create();

        // Create unread notifications
        $user->notifications()->create([
            'id' => 'test-1',
            'type' => \App\Notifications\BudgetExceededNotification::class,
            'data' => ['type' => 'budget_exceeded'],
            'read_at' => null,
        ]);

        $user->notifications()->create([
            'id' => 'test-2',
            'type' => \App\Notifications\GoalReachedNotification::class,
            'data' => ['type' => 'goal_reached'],
            'read_at' => null,
        ]);

        $this->assertEquals(2, $user->unreadNotifications()->count());

        $this->notificationService->markAllNotificationsAsRead($user);

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    /** @test */
    public function it_gets_user_notification_preferences()
    {
        $user = User::factory()->create();

        $preferences = $this->notificationService->getUserNotificationPreferences($user);

        $this->assertArrayHasKey('email_notifications', $preferences);
        $this->assertArrayHasKey('budget_alerts', $preferences);
        $this->assertArrayHasKey('goal_reached', $preferences);
        $this->assertArrayHasKey('debt_reminders', $preferences);
        $this->assertArrayHasKey('transaction_updates', $preferences);

        $this->assertTrue($preferences['email_notifications']);
    }

    /** @test */
    public function it_handles_notification_errors_gracefully()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
        ]);

        // Mock a failure scenario
        Notification::shouldReceive('send')
            ->andThrow(new \Exception('Notification failed'));

        // Should not throw an exception
        $this->notificationService->sendBudgetExceededNotification($budget);

        $this->assertTrue(true); // Test passes if no exception is thrown
    }
}