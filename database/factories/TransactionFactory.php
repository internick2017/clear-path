<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $types = ['income', 'expense'];
        $categories = [
            'Salary', 'Freelance', 'Groceries', 'Dining Out', 'Transportation', 
            'Utilities', 'Entertainment', 'Shopping', 'Healthcare', 'Education'
        ];

        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement($types),
            'category' => $this->faker->randomElement($categories),
            'amount' => $this->faker->randomFloat(2, 10, 5000),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'note' => $this->faker->optional()->sentence()
        ];
    }
} 