<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetExceededNotification extends Notification
{
    use Queueable;

    protected $budget;
    protected $spentAmount;

    /**
     * Create a new notification instance.
     */
    public function __construct($budget, $spentAmount = null)
    {
        $this->budget = $budget;
        $this->spentAmount = $spentAmount ?? $budget->spent;
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
        $overAmount = $this->budget->spent - $this->budget->limit;
        $url = route('budgets.index');

        return (new MailMessage)
            ->subject('Budget Exceeded!')
            ->greeting('Budget Alert!')
            ->line("You have exceeded the limit for category: {$this->budget->category}")
            ->line("Category: {$this->budget->category}")
            ->line("Monthly Limit: $" . number_format($this->budget->limit, 2))
            ->line("Amount Spent: $" . number_format($this->budget->spent, 2))
            ->line("Over Budget By: $" . number_format($overAmount, 2))
            ->action('View Budgets', $url)
            ->line('Please review your spending and adjust your budget if necessary.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'budget_exceeded',
            'budget_id' => $this->budget->id,
            'title' => 'Budget Exceeded!',
            'message' => "You have exceeded the limit for category '{$this->budget->category}' by $" . number_format($this->budget->spent - $this->budget->limit, 2),
            'data' => [
                'budget' => $this->budget->toArray()
            ]
        ];
    }
}
