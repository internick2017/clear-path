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
                'amount' => $debt->total_amount ?? $debt->amount,
                'amount_paid' => $debt->amount_paid ?? 0,
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
     * Since total_amount already includes all interest, we use simple division.
     * Takes into account amount already paid.
     */
    protected function estimateMonthsWithExtra(Debt $debt, float $monthly_payment): int
    {
        // Use total_amount (which includes all interest) for simple calculation
        $totalAmount = $debt->total_amount ?? $debt->amount;
        $amountPaid = $debt->amount_paid ?? 0;
        $payment = $monthly_payment;

        // Calculate remaining balance
        $remainingBalance = $totalAmount - $amountPaid;

        // Simple calculation: remaining balance divided by monthly payment (including extra)
        if ($payment <= 0) {
            return -1; // invalid payment
        }

        if ($remainingBalance <= 0) {
            return 0; // already paid off
        }

        $months = ceil($remainingBalance / $payment);
        return $months;
    }

    /**
     * Estimate months to pay off a debt using minimum payment.
     * Since total_amount already includes all interest, we use simple division.
     * Takes into account amount already paid.
     *
     * @param Debt $debt
     * @return int
     */
    protected function estimateMonths(Debt $debt): int
    {
        // Use total_amount (which includes all interest) for simple calculation
        $totalAmount = $debt->total_amount ?? $debt->amount;
        $amountPaid = $debt->amount_paid ?? 0;
        $payment = $debt->minimum_payment;

        // Calculate remaining balance
        $remainingBalance = $totalAmount - $amountPaid;

        // Simple calculation: remaining balance divided by monthly payment
        if ($payment <= 0) {
            return -1; // invalid payment
        }

        if ($remainingBalance <= 0) {
            return 0; // already paid off
        }

        $months = ceil($remainingBalance / $payment);
        return $months;
    }
}
