<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-scheduled
                            {--debt-reminders : Send debt payment reminders}
                            {--budget-alerts : Send budget exceeded alerts}
                            {--goal-reached : Check and send goal reached notifications}
                            {--all : Send all types of notifications}
                            {--days=7 : Days before due date for debt reminders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled notifications for the financial management system';

    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting scheduled notification process...');

        $totalSent = 0;

        // Send debt payment reminders
        if ($this->option('debt-reminders') || $this->option('all')) {
            $days = $this->option('days');
            $sentCount = $this->notificationService->sendScheduledDebtReminders($days);
            $this->info("Sent {$sentCount} debt payment reminders");
            $totalSent += $sentCount;
        }

        // Send budget exceeded alerts
        if ($this->option('budget-alerts') || $this->option('all')) {
            $month = Carbon::now()->format('Y-m');
            $sentCount = $this->notificationService->sendBudgetExceededAlerts($month);
            $this->info("Sent {$sentCount} budget exceeded alerts for {$month}");
            $totalSent += $sentCount;
        }

        // Check and send goal reached notifications
        if ($this->option('goal-reached') || $this->option('all')) {
            $sentCount = $this->notificationService->checkAndSendGoalReachedNotifications();
            $this->info("Sent {$sentCount} goal reached notifications");
            $totalSent += $sentCount;
        }

        // Cleanup old notifications
        $deletedCount = $this->notificationService->cleanupOldNotifications(90);
        $this->info("Cleaned up {$deletedCount} old notifications");

        $this->info("Notification process completed. Total notifications sent: {$totalSent}");

        return 0;
    }
}