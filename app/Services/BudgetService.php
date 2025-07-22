<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\BudgetExceededNotification;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class BudgetService
{
    /**
     * Update budget spent amount when a transaction is created
     * 
     * @param Transaction $transaction
     * @return void
     */
    public function handleTransactionCreated(Transaction $transaction): void
    {
        if ($transaction->type === 'expense') {
            $this->recalculateBudgetSpent($transaction->user, $transaction->category, $transaction->date);
        }
    }

    /**
     * Update budget spent amount when a transaction is updated
     * 
     * @param Transaction $transaction
     * @param array $oldData
     * @return void
     */
    public function handleTransactionUpdated(Transaction $transaction, array $oldData): void
    {
        // If the transaction changed from/to expense, or category changed, recalculate both categories
        if ($transaction->type === 'expense' || $oldData['type'] === 'expense') {
            if ($transaction->category !== $oldData['category']) {
                // Recalculate old category
                $this->recalculateBudgetSpent($transaction->user, $oldData['category'], $transaction->date);
            }
            // Recalculate current category
            $this->recalculateBudgetSpent($transaction->user, $transaction->category, $transaction->date);
        }
    }

    /**
     * Update budget spent amount when a transaction is deleted
     * 
     * @param Transaction $transaction
     * @return void
     */
    public function handleTransactionDeleted(Transaction $transaction): void
    {
        if ($transaction->type === 'expense') {
            $this->recalculateBudgetSpent($transaction->user, $transaction->category, $transaction->date);
        }
    }

    /**
     * Recalculate budget spent amount for a specific category and month
     * 
     * @param User $user
     * @param string $category
     * @param string $date
     * @return void
     */
    public function recalculateBudgetSpent(User $user, string $category, string $date): void
    {
        $transactionDate = Carbon::parse($date);
        
        $budget = Budget::where('user_id', $user->id)
            ->where('category', $category)
            ->whereMonth('month', $transactionDate->month)
            ->whereYear('month', $transactionDate->year)
            ->first();

        if (!$budget) {
            return;
        }

        // Calculate actual spent amount from transactions
        $actualSpent = $user->transactions()
            ->where('type', 'expense')
            ->where('category', $category)
            ->whereMonth('date', $transactionDate->month)
            ->whereYear('date', $transactionDate->year)
            ->sum('amount');

        $budget->update(['spent' => $actualSpent]);

        // Check if budget is exceeded and send notification
        if ($actualSpent >= ($budget->limit * 0.9)) {
            Notification::send($user, new BudgetExceededNotification($budget, $actualSpent));
        }
    }

    /**
     * Check if a budget category is close to or has exceeded its limit
     * 
     * @param User $user
     * @param string $category
     * @param float $amount
     * @return bool
     */
    public function checkBudgetLimit(User $user, string $category, float $amount): bool
    {
        $budget = Budget::where('user_id', $user->id)
            ->where('category', $category)
            ->whereMonth('month', now()->month)
            ->whereYear('month', now()->year)
            ->first();

        if (!$budget) {
            return false;
        }

        $newSpentAmount = $budget->spent + $amount;
        $budget->update(['spent' => $newSpentAmount]);

        // Notify if spent amount exceeds 90% of budget limit
        if ($newSpentAmount >= ($budget->limit * 0.9)) {
            Notification::send($user, new BudgetExceededNotification($budget, $newSpentAmount));
            return true;
        }

        return false;
    }

    /**
     * Reset monthly budgets
     */
    public function resetMonthlyBudgets()
    {
        Budget::where('month', '<', now()->startOfMonth())->update([
            'spent' => 0,
            'month' => now()->format('Y-m-d')
        ]);
    }

    /**
     * Create a new budget for a user
     * 
     * @param User $user
     * @param string $category
     * @param float $limit
     * @return Budget
     */
    public function createBudget(User $user, string $category, float $limit): Budget
    {
        return Budget::create([
            'user_id' => $user->id,
            'category' => $category,
            'limit' => $limit,
            'spent' => 0,
            'month' => now()->format('Y-m-d')
        ]);
    }
} 