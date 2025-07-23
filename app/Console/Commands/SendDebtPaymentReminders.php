<?php

namespace App\Console\Commands;

use App\Models\Debt;
use App\Notifications\DebtPaymentReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDebtPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-debt-reminders {--days=7 : Days before due date to send reminder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send debt payment reminders to users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysBeforeDue = $this->option('days');
        $reminderDate = Carbon::now()->addDays($daysBeforeDue);

        $debts = Debt::where('status', 'active')
            ->where('due_date', '<=', $reminderDate)
            ->where('due_date', '>', Carbon::now())
            ->with('user')
            ->get();

        $this->info("Found {$debts->count()} debts with upcoming payments.");

        $sentCount = 0;
        foreach ($debts as $debt) {
            try {
                $debt->user->notify(new DebtPaymentReminderNotification($debt));
                $sentCount++;
                $this->line("Sent reminder for debt: {$debt->name} (User: {$debt->user->email})");
            } catch (\Exception $e) {
                $this->error("Failed to send reminder for debt {$debt->id}: " . $e->getMessage());
            }
        }

        $this->info("Successfully sent {$sentCount} debt payment reminders.");
    }
}