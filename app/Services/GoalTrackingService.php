<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\User;
use App\Notifications\GoalReachedNotification;
use Illuminate\Support\Facades\Notification;

class GoalTrackingService
{
    /**
     * Update goal progress
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