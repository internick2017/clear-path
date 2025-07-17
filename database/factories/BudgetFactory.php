<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        $categories = [
            'Groceries', 'Dining Out', 'Transportation', 'Utilities', 
            'Entertainment', 'Shopping', 'Healthcare', 'Education'
        ];

        return [
            'user_id' => User::factory(),
            'category' => $this->faker->randomElement($categories),
            'limit' => $this->faker->randomFloat(2, 100, 2000),
            'spent' => $this->faker->randomFloat(2, 0, 1500),
            'month' => now()->format('Y-m-d')
        ];
    }
} 