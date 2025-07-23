<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GoalReachedNotification extends Notification
{
    use Queueable;

    protected $goal;

    /**
     * Create a new notification instance.
     */
    public function __construct($goal)
    {
        $this->goal = $goal;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $url = route('goals.show', $this->goal);
        $deadline = is_string($this->goal->deadline) ? $this->goal->deadline : $this->goal->deadline->format('F d, Y');

        return (new MailMessage)
            ->subject('Goal Reached! 🎉')
            ->greeting('Congratulations! 🎉')
            ->line("You have successfully reached your goal: {$this->goal->title}")
            ->line("Goal Title: {$this->goal->title}")
            ->line("Target Amount: $" . number_format($this->goal->target_amount, 2))
            ->line("Current Amount: $" . number_format($this->goal->current_amount, 2))
            ->line("Deadline: {$deadline}")
            ->action('View Goal', $url)
            ->line('Great job on achieving your financial goal!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'goal_reached',
            'title' => 'Goal Reached!',
            'message' => "Congratulations! You have reached your goal '{$this->goal->title}' with $" . number_format($this->goal->current_amount, 2) . " of $" . number_format($this->goal->target_amount, 2),
            'category' => 'Savings Goal',
            'goal_id' => $this->goal->id,
            'current_amount' => $this->goal->current_amount,
            'target_amount' => $this->goal->target_amount,
            'data' => [
                'goal' => $this->goal->toArray()
            ]
        ];
    }
}
