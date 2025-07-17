<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        $targetAmount = $this->faker->randomFloat(2, 500, 50000);
        $currentAmount = $this->faker->randomFloat(2, 0, $targetAmount);

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->randomElement([
                'Emergency Fund', 'Vacation', 'New Car', 'Home Down Payment', 
                'Retirement Savings', 'Education Fund'
            ]),
            'target_amount' => $targetAmount,
            'current_amount' => $currentAmount,
            'deadline' => $this->faker->dateTimeBetween('now', '+3 years')
        ];
    }
} 