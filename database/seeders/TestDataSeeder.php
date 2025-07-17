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
    }
}
