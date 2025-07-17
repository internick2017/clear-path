<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\User;

class DebtPayoffService
{
    /**
     * Generate payoff plan for user using Snowball or Avalanche method.
     *
     * @param User $user
     * @param string $method 'snowball' or 'avalanche'
     * @return array
     */
    public function generatePlan(User $user, string $method = 'snowball'): array
    {
        $debts = $user->debts()->get();
        if ($method === 'snowball') {
            $debts = $debts->sortBy('amount');
        } elseif ($method === 'avalanche') {
            $debts = $debts->sortByDesc('interest_rate');
        }

        $plan = [];
        foreach ($debts as $debt) {
            $months = $this->estimateMonths($debt);
            $plan[] = [
                'name' => $debt->name,
                'amount' => $debt->amount,
                'interest_rate' => $debt->interest_rate,
                'minimum_payment' => $debt->minimum_payment,
                'due_date' => $debt->due_date,
                'strategy' => $debt->strategy,
                'estimated_months' => $months,
            ];
        }
        return $plan;
    }

    /**
     * Estimate months to pay off a debt using minimum payment and interest.
     *
     * @param Debt $debt
     * @return int
     */
    protected function estimateMonths(Debt $debt): int
    {
        $balance = $debt->amount;
        $rate = $debt->interest_rate / 100 / 12;
        $payment = $debt->minimum_payment;
        $months = 0;
        // Simple estimation: stop if payment is not enough to cover interest
        if ($payment <= $balance * $rate) {
            return -1; // never paid off
        }
        while ($balance > 0 && $months < 600) {
            $interest = $balance * $rate;
            $balance = $balance + $interest - $payment;
            $months++;
            if ($balance < 0) $balance = 0;
        }
        return $months;
    }
}
