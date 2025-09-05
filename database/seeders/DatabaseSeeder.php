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

        // Create Nick Granados user
        $nickUser = User::firstOrCreate(
            ['email' => 'nickgranados01@gmail.com'],
            [
                'name' => 'Nick Granados',
                'password' => bcrypt('d165218l')
            ]
        );

        // Create sample transactions with expense types
        $categories = [
            'Groceries' => 'variable',
            'Utilities' => 'fixed',
            'Transportation' => 'fixed',
            'Entertainment' => 'variable',
            'Healthcare' => 'variable',
            'Rent' => 'fixed',
            'Salary' => 'income'
        ];

        foreach ($categories as $category => $type) {
            // Create transactions for the last 3 months
            for ($i = 0; $i < 3; $i++) {
                $date = now()->subMonths($i);
                $amount = $type === 'income' ? rand(3000, 5000) : rand(100, 800);

                $user->transactions()->create([
                    'type' => $type === 'income' ? 'income' : 'expense',
                    'category' => $category,
                    'amount' => $amount,
                    'date' => $date->format('Y-m-d'),
                    'expense_type' => $type === 'income' ? null : $type,
                    'note' => "Sample $category transaction"
                ]);
            }
        }

        // Create budgets for current month
        $budgetCategories = ['Groceries', 'Utilities', 'Transportation', 'Entertainment'];
        foreach ($budgetCategories as $category) {
            $user->budgets()->create([
                'category' => $category,
                'limit' => rand(300, 800),
                'spent' => rand(50, 400),
                'month' => now()->format('Y-m-d')
            ]);
        }

        // Create a sample goal
        $user->goals()->create([
            'title' => 'Emergency Fund',
            'target_amount' => 5000,
            'current_amount' => 1200,
            'deadline' => now()->addMonths(6)->format('Y-m-d')
        ]);

        // Create a sample debt
        $user->debts()->create([
            'name' => 'Credit Card',
            'amount' => 2500,
            'interest_rate' => 18.5,
            'minimum_payment' => 125,
            'due_date' => now()->addDays(15)->format('Y-m-d'),
            'strategy' => 'snowball',
            'status' => 'active'
        ]);
    }
}
