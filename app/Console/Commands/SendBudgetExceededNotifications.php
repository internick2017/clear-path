<?php

namespace App\Console\Commands;

use App\Models\Budget;
use App\Notifications\BudgetExceededNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendBudgetExceededNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-budget-alerts {--month= : Specific month to check (YYYY-MM format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send budget exceeded notifications to users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = $this->option('month') ?? Carbon::now()->format('Y-m');

        $budgets = Budget::where('month', $month)
            ->whereRaw('spent > `limit`')
            ->with('user')
            ->get();

        $this->info("Found {$budgets->count()} exceeded budgets for {$month}.");

        $sentCount = 0;
        foreach ($budgets as $budget) {
            try {
                $budget->user->notify(new BudgetExceededNotification($budget));
                $sentCount++;
                $this->line("Sent budget alert for: {$budget->category} (User: {$budget->user->email})");
            } catch (\Exception $e) {
                $this->error("Failed to send budget alert for budget {$budget->id}: " . $e->getMessage());
            }
        }

        $this->info("Successfully sent {$sentCount} budget exceeded notifications.");
    }
}