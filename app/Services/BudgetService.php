<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\User;
use App\Notifications\BudgetExceededNotification;
use Illuminate\Support\Facades\Notification;

class BudgetService
{
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
            'month' => now()->startOfMonth()
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
            'month' => now()->format('Y-m')
        ]);
    }
} 