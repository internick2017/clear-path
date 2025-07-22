<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all users in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all(['name', 'email']);

        if ($users->isEmpty()) {
            $this->error('No users found in the database.');
            return 1;
        }

        $this->info('Users in the database:');
        $this->newLine();

        foreach ($users as $user) {
            $this->line("Name: {$user->name}");
            $this->line("Email: {$user->email}");
            $this->line("Password: password (default for all users)");
            $this->newLine();
        }

        $this->info('You can use any of these credentials to login and test the application.');
        
        return 0;
    }
}
