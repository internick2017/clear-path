<?php

namespace App\Notifications;

use App\Models\Debt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DebtPaymentReminderNotification extends Notification
{
    use Queueable;

    /**
     * The debt instance
     * 
     * @var Debt
     */
    protected $debt;

    /**
     * Create a new notification instance.
     * 
     * @param Debt $debt
     */
    public function __construct(Debt $debt)
    {
        $this->debt = $debt;
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
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Upcoming Debt Payment Reminder')
            ->line("Reminder: You have an upcoming payment for {$this->debt->name}")
            ->line("Minimum Payment: $" . number_format($this->debt->minimum_payment, 2))
            ->line("Due Date: " . $this->debt->due_date->format('F d, Y'))
            ->action('View Debt Details', route('debts.index'))
            ->line('Please ensure you make your payment on time to avoid late fees.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'debt_id' => $this->debt->id,
            'debt_name' => $this->debt->name,
            'minimum_payment' => $this->debt->minimum_payment,
            'due_date' => $this->debt->due_date,
        ];
    }
} 