<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\GoalReachedNotification;
use Illuminate\Support\Facades\Notification;

class GoalTrackingService
{
    /**
     * Handle transaction created event for goal tracking
     * 
     * @param Transaction $transaction
     * @return void
     */
    public function handleTransactionCreated(Transaction $transaction): void
    {
        // Only track income transactions that are categorized as savings
        if ($transaction->type === 'income' && $this->isSavingsTransaction($transaction)) {
            $this->updateGoalsFromSavings($transaction->user, $transaction->amount);
        }
    }

    /**
     * Handle transaction updated event for goal tracking
     * 
     * @param Transaction $transaction
     * @param array $oldData
     * @return void
     */
    public function handleTransactionUpdated(Transaction $transaction, array $oldData): void
    {
        $oldWasSavings = $oldData['type'] === 'income' && $this->isSavingsTransaction((object)$oldData);
        $newIsSavings = $transaction->type === 'income' && $this->isSavingsTransaction($transaction);

        if ($oldWasSavings || $newIsSavings) {
            // Recalculate all goals for this user
            $this->recalculateAllGoals($transaction->user);
        }
    }

    /**
     * Handle transaction deleted event for goal tracking
     * 
     * @param Transaction $transaction
     * @return void
     */
    public function handleTransactionDeleted(Transaction $transaction): void
    {
        if ($transaction->type === 'income' && $this->isSavingsTransaction($transaction)) {
            $this->recalculateAllGoals($transaction->user);
        }
    }

    /**
     * Check if a transaction is a savings transaction
     * 
     * @param Transaction|object $transaction
     * @return bool
     */
    private function isSavingsTransaction($transaction): bool
    {
        $savingsCategories = ['savings', 'investment', 'goal'];
        return in_array(strtolower($transaction->category), $savingsCategories);
    }

    /**
     * Update goals from savings amount
     * 
     * @param User $user
     * @param float $amount
     * @return void
     */
    private function updateGoalsFromSavings(User $user, float $amount): void
    {
        $activeGoals = $user->goals()
            ->whereRaw('CAST(current_amount AS DECIMAL(10,2)) < CAST(target_amount AS DECIMAL(10,2))')
            ->where('deadline', '>', now()->format('Y-m-d'))
            ->orderBy('deadline', 'asc')
            ->get();

        if ($activeGoals->isEmpty()) {
            return;
        }

        $remainingAmount = $amount;
        
        foreach ($activeGoals as $goal) {
            if ($remainingAmount <= 0) {
                break;
            }

            $neededAmount = $goal->target_amount - $goal->current_amount;
            $amountToAdd = min($remainingAmount, $neededAmount);
            
            $goal->current_amount += $amountToAdd;
            $goal->save();

            $remainingAmount -= $amountToAdd;

            // Check if goal is reached
            if ($goal->current_amount >= $goal->target_amount) {
                Notification::send($user, new GoalReachedNotification($goal));
            }
        }
    }

    /**
     * Recalculate all goals for a user based on savings transactions
     * 
     * @param User $user
     * @return void
     */
    private function recalculateAllGoals(User $user): void
    {
        $goals = $user->goals()->orderBy('created_at', 'asc')->get();
        
        // Reset all goals to 0
        $user->goals()->update(['current_amount' => 0]);

        // Get all savings transactions
        $savingsTransactions = $user->transactions()
            ->where('type', 'income')
            ->whereIn('category', ['savings', 'investment', 'goal'])
            ->orderBy('date', 'asc')
            ->get();

        // Redistribute savings across goals
        foreach ($savingsTransactions as $transaction) {
            $this->updateGoalsFromSavings($user, $transaction->amount);
        }
    }

    /**
     * Update goal progress manually
     * 
     * @param Goal $goal
     * @param float $amount
     * @return bool
     */
    public function updateGoalProgress(Goal $goal, float $amount): bool
    {
        $newCurrentAmount = $goal->current_amount + $amount;
        $goal->update(['current_amount' => $newCurrentAmount]);

        // Check if goal is reached
        if ($newCurrentAmount >= $goal->target_amount) {
            Notification::send($goal->user, new GoalReachedNotification($goal));
            return true;
        }

        return false;
    }

    /**
     * Check goal progress and send reminders
     * 
     * @param User $user
     */
    public function checkGoalProgress(User $user)
    {
        $goals = $user->goals()->where('deadline', '>', now())->get();

        foreach ($goals as $goal) {
            $progressPercentage = ($goal->current_amount / $goal->target_amount) * 100;
            
            // Send reminder if goal is less than 50% complete and more than 50% of time has passed
            $timeProgress = (now()->diffInDays($goal->deadline) / now()->diffInDays($goal->created_at)) * 100;
            
            if ($progressPercentage < 50 && $timeProgress > 50) {
                // You could create a new notification for this scenario
                // Notification::send($user, new GoalProgressReminderNotification($goal));
            }
        }
    }

    /**
     * Create a new goal for a user
     * 
     * @param User $user
     * @param string $title
     * @param float $targetAmount
     * @param \DateTime $deadline
     * @return Goal
     */
    public function createGoal(User $user, string $title, float $targetAmount, \DateTime $deadline): Goal
    {
        return Goal::create([
            'user_id' => $user->id,
            'title' => $title,
            'target_amount' => $targetAmount,
            'current_amount' => 0,
            'deadline' => $deadline
        ]);
    }
} 