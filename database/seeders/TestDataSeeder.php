<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::first();
        if (!$user) return;

        $categories = ['Food', 'Transport', 'Entertainment', 'Health'];
        $month = now()->startOfMonth();

        foreach ($categories as $cat) {
            $budget = \App\Models\Budget::create([
                'user_id' => $user->id,
                'category' => $cat,
                'limit' => rand(100, 500),
                'spent' => 0,
                'month' => $month,
            ]);

            for ($i = 0; $i < rand(2, 5); $i++) {
                $amount = rand(10, 100);
                \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'expense',
                    'category' => $cat,
                    'amount' => $amount,
                    'date' => now()->subDays(rand(0, 28)),
                    'note' => 'Test expense',
                ]);
                $budget->spent += $amount;
            }
            $budget->save();
        }

        // Add test debts for payoff simulation
        $debts = [
            [
                'name' => 'Visa',
                'amount' => 1200,
                'interest_rate' => 17.9,
                'minimum_payment' => 100,
                'due_date' => now()->addMonths(12),
                'strategy' => 'snowball',
            ],
            [
                'name' => 'Mastercard',
                'amount' => 800,
                'interest_rate' => 22.5,
                'minimum_payment' => 50,
                'due_date' => now()->addMonths(10),
                'strategy' => 'avalanche',
            ],
            [
                'name' => 'Car Loan',
                'amount' => 5000,
                'interest_rate' => 6.5,
                'minimum_payment' => 200,
                'due_date' => now()->addMonths(36),
                'strategy' => 'snowball',
            ],
        ];
        foreach ($debts as $debt) {
            \App\Models\Debt::create(array_merge($debt, [
                'user_id' => $user->id,
            ]));
        }
    }
}
