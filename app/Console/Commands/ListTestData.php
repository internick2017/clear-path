<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\Debt;
use Illuminate\Console\Command;

class ListTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show test data for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== ClearPath Test Data ===');
        $this->newLine();

        // Show users
        $users = User::all();
        $this->info("Users ({$users->count()}):");
        foreach ($users as $user) {
            $this->line("  • {$user->name} ({$user->email}) - Password: password");
        }
        $this->newLine();

        // Show data for each user
        foreach ($users as $user) {
            $this->info("Data for {$user->name}:");
            
            $transactions = Transaction::where('user_id', $user->id)->count();
            $budgets = Budget::where('user_id', $user->id)->count();
            $goals = Goal::where('user_id', $user->id)->count();
            $debts = Debt::where('user_id', $user->id)->count();
            
            $this->line("  • Transactions: {$transactions}");
            $this->line("  • Budgets: {$budgets}");
            $this->line("  • Goals: {$goals}");
            $this->line("  • Debts: {$debts}");
            $this->newLine();
        }

        $this->info('=== Login Credentials ===');
        $this->line('You can use any of these accounts to test the application:');
        $this->newLine();
        
        foreach ($users as $user) {
            $this->line("Email: {$user->email}");
            $this->line("Password: password");
            $this->newLine();
        }

        $this->info('=== Testing Instructions ===');
        $this->line('1. Start the development server: php artisan serve');
        $this->line('2. Visit: http://localhost:8000');
        $this->line('3. Login with any of the credentials above');
        $this->line('4. Test all features: Dashboard, Transactions, Budgets, Goals, Debts');
        $this->line('5. The charts should now display properly without infinite expansion');
        
        return 0;
    }
}
