<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Debt;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\BudgetExceededNotification;
use App\Notifications\DebtPaymentReminderNotification;
use App\Notifications\GoalReachedNotification;
use App\Notifications\TransactionCategoryChangeNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send budget exceeded notification
     */
    public function sendBudgetExceededNotification(Budget $budget): void
    {
        try {
            $budget->user->notify(new BudgetExceededNotification($budget));
            Log::info("Budget exceeded notification sent for budget {$budget->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send budget exceeded notification: " . $e->getMessage());
        }
    }

    /**
     * Send goal reached notification
     */
    public function sendGoalReachedNotification(Goal $goal): void
    {
        try {
            $goal->user->notify(new GoalReachedNotification($goal));
            Log::info("Goal reached notification sent for goal {$goal->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send goal reached notification: " . $e->getMessage());
        }
    }

    /**
     * Send debt payment reminder notification
     */
    public function sendDebtPaymentReminder(Debt $debt): void
    {
        try {
            $debt->user->notify(new DebtPaymentReminderNotification($debt));
            Log::info("Debt payment reminder sent for debt {$debt->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send debt payment reminder: " . $e->getMessage());
        }
    }

    /**
     * Send transaction category change notification
     */
    public function sendTransactionCategoryChangeNotification(Transaction $transaction, string $previousCategory): void
    {
        try {
            $transaction->user->notify(new TransactionCategoryChangeNotification($transaction, $previousCategory));
            Log::info("Transaction category change notification sent for transaction {$transaction->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send transaction category change notification: " . $e->getMessage());
        }
    }

    /**
     * Send scheduled debt payment reminders
     */
    public function sendScheduledDebtReminders(int $daysBeforeDue = 7): int
    {
        $reminderDate = Carbon::now()->addDays($daysBeforeDue);

        $debts = Debt::where('status', 'active')
            ->where('due_date', '<=', $reminderDate)
            ->where('due_date', '>', Carbon::now())
            ->with('user')
            ->get();

        $sentCount = 0;
        foreach ($debts as $debt) {
            $this->sendDebtPaymentReminder($debt);
            $sentCount++;
        }

        Log::info("Sent {$sentCount} scheduled debt payment reminders");
        return $sentCount;
    }

        /**
     * Send budget exceeded alerts for a specific month
     */
    public function sendBudgetExceededAlerts(string $month = null): int
    {
        $month = $month ?? Carbon::now()->format('Y-m');

        // Convert Y-m format to start of month date
        $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $budgets = Budget::where('month', $startOfMonth)
            ->whereRaw('spent > `limit`')
            ->with('user')
            ->get();

        $sentCount = 0;
        foreach ($budgets as $budget) {
            $this->sendBudgetExceededNotification($budget);
            $sentCount++;
        }

        Log::info("Sent {$sentCount} budget exceeded alerts for {$month}");
        return $sentCount;
    }

    /**
     * Check and send goal reached notifications
     */
    public function checkAndSendGoalReachedNotifications(): int
    {
        $goals = Goal::whereRaw('current_amount >= target_amount')
            ->with('user')
            ->get();

        $sentCount = 0;
        foreach ($goals as $goal) {
            $this->sendGoalReachedNotification($goal);
            $sentCount++;
        }

        Log::info("Sent {$sentCount} goal reached notifications");
        return $sentCount;
    }

    /**
     * Get notification statistics for a user
     */
    public function getUserNotificationStats(User $user): array
    {
        $totalNotifications = $user->notifications()->count();
        $unreadNotifications = $user->unreadNotifications()->count();
        $recentNotifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'total' => $totalNotifications,
            'unread' => $unreadNotifications,
            'recent' => $recentNotifications,
        ];
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllNotificationsAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
        Log::info("Marked all notifications as read for user {$user->id}");
    }

    /**
     * Delete old notifications (older than specified days)
     */
    public function cleanupOldNotifications(int $daysOld = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);
        $deletedCount = \DB::table('notifications')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        Log::info("Deleted {$deletedCount} old notifications (older than {$daysOld} days)");
        return $deletedCount;
    }

    /**
     * Get notification preferences for a user
     */
    public function getUserNotificationPreferences(User $user): array
    {
        // This could be extended to use a user preferences table
        return [
            'email_notifications' => true,
            'budget_alerts' => true,
            'goal_reached' => true,
            'debt_reminders' => true,
            'transaction_updates' => true,
        ];
    }
}