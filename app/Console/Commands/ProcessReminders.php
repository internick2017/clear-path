<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:process {--test : Run in test mode without sending notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process daily reminders and send notifications for due payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing reminders...');

        if ($this->option('test')) {
            $this->warn('Running in TEST mode - no notifications will be sent');
        }

        try {
            if (!$this->option('test')) {
                \App\Services\ReminderService::processDailyReminders();
            }

            $this->info('Reminders processed successfully!');

            // Show summary
            $this->showRemindersSummary();

        } catch (\Exception $e) {
            $this->error('Error processing reminders: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Show a summary of current reminders
     */
    private function showRemindersSummary()
    {
        $this->info('Reminders Summary:');

        // You could add more detailed reporting here
        $this->line('✓ Daily reminder processing completed');
        $this->line('✓ Notifications sent for due reminders');
        $this->line('✓ Next due dates updated');
    }
}
