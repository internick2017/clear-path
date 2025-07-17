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
    public function generatePlan(User $user, string $method = 'snowball', float $extra_payment = 0): array
    {
        $debts = $user->debts()->get();
        if ($method === 'snowball') {
            $debts = $debts->sortBy('amount');
        } elseif ($method === 'avalanche') {
            $debts = $debts->sortByDesc('interest_rate');
        }

        $plan = [];
        // Prepare debts as array for easier manipulation
        $debtsArr = $debts->values()->all();
        $extra = $extra_payment;
        $payments = [];
        foreach ($debtsArr as $i => $debt) {
            $payments[$i] = $debt->minimum_payment;
        }

        // Simulate payoff with extra payment
        $with_extra_months = [];
        $remaining_balances = [];
        $total_payment = 0;
        $n = count($debtsArr);
        for ($i = 0; $i < $n; $i++) {
            $total_payment = $debtsArr[$i]->minimum_payment + $extra;
            $with_extra_months[$i] = $this->estimateMonthsWithExtra($debtsArr[$i], $total_payment);
            $extra = 0; // After first debt, extra is 0 (snowball effect)
        }

        foreach ($debtsArr as $i => $debt) {
            $months = $this->estimateMonths($debt);
            $plan[] = [
                'name' => $debt->name,
                'amount' => $debt->amount,
                'interest_rate' => $debt->interest_rate,
                'minimum_payment' => $debt->minimum_payment,
                'due_date' => $debt->due_date,
                'strategy' => $debt->strategy,
                'estimated_months' => $months,
                'with_extra_payment' => $with_extra_months[$i],
            ];
        }
        return $plan;
    }

    /**
     * Estimate months to pay off a debt with extra payment.
     */
    protected function estimateMonthsWithExtra(Debt $debt, float $monthly_payment): int
    {
        $balance = $debt->amount;
        $rate = $debt->interest_rate / 100 / 12;
        $payment = $monthly_payment;
        $months = 0;
        if ($payment <= $balance * $rate) {
            return -1;
        }
        while ($balance > 0 && $months < 600) {
            $interest = $balance * $rate;
            $balance = $balance + $interest - $payment;
            $months++;
            if ($balance < 0) $balance = 0;
        }
        return $months;
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
