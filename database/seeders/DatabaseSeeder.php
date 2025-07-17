<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Budget;
use App\Models\Transaction;
use App\Models\Goal;
use App\Models\Debt;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a primary test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password')
            ]
        );

        // Create multiple financial records for the test user
        Budget::factory()->count(5)->for($user)->create();
        Transaction::factory()->count(50)->for($user)->create();
        Goal::factory()->count(3)->for($user)->create();
        Debt::factory()->count(4)->for($user)->create();

        // Optional: Create additional random users with their financial data
        User::factory()->count(5)->create()->each(function ($user) {
            Budget::factory()->count(3)->for($user)->create();
            Transaction::factory()->count(20)->for($user)->create();
            Goal::factory()->count(2)->for($user)->create();
            Debt::factory()->count(2)->for($user)->create();
        });
    }
}
