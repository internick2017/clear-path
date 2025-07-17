<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DebtFactory extends Factory
{
    protected $model = Debt::class;

    public function definition(): array
    {
        $strategies = ['snowball', 'avalanche'];
        $amount = $this->faker->randomFloat(2, 1000, 50000);
        $interestRate = $this->faker->randomFloat(2, 3, 25);
        $minimumPayment = $amount * ($this->faker->randomFloat(2, 1, 5) / 100);

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement([
                'Credit Card', 'Personal Loan', 'Student Loan', 
                'Car Loan', 'Mortgage'
            ]),
            'amount' => $amount,
            'interest_rate' => $interestRate,
            'minimum_payment' => $minimumPayment,
            'due_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'strategy' => $this->faker->randomElement($strategies),
            'status' => $this->faker->randomElement(['active', 'paid']),
            'paid_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')
        ];
    }
} 