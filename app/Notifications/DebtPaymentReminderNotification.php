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
        $daysUntilDue = $this->debt->due_date->diffInDays(now());
        $url = route('debts.index');

        return (new MailMessage)
            ->subject('Upcoming Debt Payment Reminder')
            ->greeting('Payment Reminder')
            ->line("Reminder: You have an upcoming payment for {$this->debt->name}")
            ->line("Debt Name: {$this->debt->name}")
            ->line("Minimum Payment: $" . number_format($this->debt->minimum_payment, 2))
            ->line("Due Date: " . $this->debt->due_date->format('F d, Y'))
            ->line("Days Until Due: {$daysUntilDue} days")
            ->line("Current Balance: $" . number_format($this->debt->balance, 2))
            ->action('View Debt Details', $url)
            ->line('Please make your payment on time to avoid late fees.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'debt_payment_reminder',
            'title' => 'Upcoming Debt Payment Reminder',
            'message' => "Reminder: You have an upcoming payment for {$this->debt->name}. Minimum Payment: $" . number_format($this->debt->minimum_payment, 2) . " due on " . $this->debt->due_date->format('F d, Y'),
            'category' => 'Debt Payment',
            'debt_id' => $this->debt->id,
            'debt_name' => $this->debt->name,
            'minimum_payment' => $this->debt->minimum_payment,
            'due_date' => $this->debt->due_date,
        ];
    }
}