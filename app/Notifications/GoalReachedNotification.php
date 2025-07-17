<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GoalReachedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $goal;

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
        return ['mail', 'database']; // Puedes agregar 'database' para notificaciones en la app
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Meta alcanzada!')
            ->line("Has alcanzado tu meta: {$this->goal->title}")
            ->action('Ver metas', url('/goals'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => '¡Meta alcanzada!',
            'summary' => "Has alcanzado tu meta: {$this->goal->title}",
            'url' => url('/goals'),
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
