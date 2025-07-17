<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetExceededNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $budget;

    public function __construct($budget)
    {
        $this->budget = $budget;
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
            ->subject('¡Presupuesto excedido!')
            ->line("Has excedido el límite de la categoría: {$this->budget->category}")
            ->action('Ver presupuesto', url('/dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => '¡Presupuesto excedido!',
            'summary' => "Has excedido el límite de la categoría: {$this->budget->category}",
            'url' => url('/dashboard'),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
