<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Debt;
use App\Models\Goal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendScheduledNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /** @test */
    public function it_sends_all_scheduled_notifications()
    {
        $user = User::factory()->create();

        // Create test data
        $debt = Debt::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'due_date' => Carbon::now()->addDays(5),
        ]);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'month' => Carbon::now()->startOfMonth(),
            'limit' => 100,
            'spent' => 150,
        ]);

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 1000,
            'current_amount' => 1000,
        ]);

        $this->artisan('notifications:send-scheduled', ['--all' => true])
            ->expectsOutput('Starting scheduled notification process...')
            ->expectsOutput('Sent 1 debt payment reminders')
            ->expectsOutput('Sent 1 budget exceeded alerts for ' . Carbon::now()->format('Y-m'))
            ->expectsOutput('Sent 1 goal reached notifications')
            ->expectsOutput('Cleaned up 0 old notifications')
            ->expectsOutput('Notification process completed. Total notifications sent: 3')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_only_debt_reminders_when_specified()
    {
        $user = User::factory()->create();

        $debt = Debt::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'due_date' => Carbon::now()->addDays(5),
        ]);

        $this->artisan('notifications:send-scheduled', ['--debt-reminders' => true])
            ->expectsOutput('Starting scheduled notification process...')
            ->expectsOutput('Sent 1 debt payment reminders')
            ->expectsOutput('Cleaned up 0 old notifications')
            ->expectsOutput('Notification process completed. Total notifications sent: 1')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_only_budget_alerts_when_specified()
    {
        $user = User::factory()->create();

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'month' => Carbon::now()->startOfMonth(),
            'limit' => 100,
            'spent' => 150,
        ]);

        $this->artisan('notifications:send-scheduled', ['--budget-alerts' => true])
            ->expectsOutput('Starting scheduled notification process...')
            ->expectsOutput('Sent 1 budget exceeded alerts for ' . Carbon::now()->format('Y-m'))
            ->expectsOutput('Cleaned up 0 old notifications')
            ->expectsOutput('Notification process completed. Total notifications sent: 1')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_only_goal_reached_notifications_when_specified()
    {
        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 1000,
            'current_amount' => 1000,
        ]);

        $this->artisan('notifications:send-scheduled', ['--goal-reached' => true])
            ->expectsOutput('Starting scheduled notification process...')
            ->expectsOutput('Sent 1 goal reached notifications')
            ->expectsOutput('Cleaned up 0 old notifications')
            ->expectsOutput('Notification process completed. Total notifications sent: 1')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_respects_days_parameter_for_debt_reminders()
    {
        $user = User::factory()->create();

        // Create debt due in 10 days (should not be included with default 7 days)
        $debt = Debt::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'due_date' => Carbon::now()->addDays(10),
        ]);

        $this->artisan('notifications:send-scheduled', ['--debt-reminders' => true, '--days' => 5])
            ->expectsOutput('Starting scheduled notification process...')
            ->expectsOutput('Sent 0 debt payment reminders')
            ->expectsOutput('Cleaned up 0 old notifications')
            ->expectsOutput('Notification process completed. Total notifications sent: 0')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_empty_data_gracefully()
    {
        $this->artisan('notifications:send-scheduled', ['--all' => true])
            ->expectsOutput('Starting scheduled notification process...')
            ->expectsOutput('Sent 0 debt payment reminders')
            ->expectsOutput('Sent 0 budget exceeded alerts for ' . Carbon::now()->format('Y-m'))
            ->expectsOutput('Sent 0 goal reached notifications')
            ->expectsOutput('Cleaned up 0 old notifications')
            ->expectsOutput('Notification process completed. Total notifications sent: 0')
            ->assertExitCode(0);
    }
}