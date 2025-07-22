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
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Goal Reached!')
            ->line("Congratulations! You have reached your goal: {$this->goal->title}")
            ->line("Current amount: $" . number_format($this->goal->current_amount, 2))
            ->line("Target amount: $" . number_format($this->goal->target_amount, 2))
            ->action('View Goal', route('goals.show', $this->goal))
            ->line('Keep up the great work!');
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
            'goal_id' => $this->goal->id,
            'title' => 'Goal Reached!',
            'message' => "Congratulations! You have reached your goal '{$this->goal->title}' with $" . number_format($this->goal->current_amount, 2) . " of $" . number_format($this->goal->target_amount, 2),
            'data' => [
                'goal' => $this->goal->toArray()
            ]
        ];
    }
}
