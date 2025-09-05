<?php

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reminder;

    /**
     * Create a new notification instance.
     */
    public function __construct(Reminder $reminder)
    {
        $this->reminder = $reminder;
    }

    /**
     * Get the notification's delivery channels.
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
        $subject = $this->reminder->isOverdue()
            ? "¡Recordatorio Vencido: {$this->reminder->title}!"
            : "Recordatorio: {$this->reminder->title}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("¡Hola {$notifiable->name}!")
            ->line("Te recordamos que tienes un pago pendiente:")
            ->line("**{$this->reminder->title}**");

        if ($this->reminder->description) {
            $mail->line($this->reminder->description);
        }

        if ($this->reminder->amount) {
            $mail->line("Monto aproximado: $" . number_format($this->reminder->amount, 2));
        }

        $mail->line("Fecha límite: " . $this->reminder->next_due_date->format('d/m/Y'));

        if ($this->reminder->isOverdue()) {
            $mail->line("⚠️ Este pago está vencido. Te recomendamos pagarlo lo antes posible.");
        }

        return $mail->action('Ver Recordatorios', url('/reminders'))
            ->line('¡Gracias por mantener tus finanzas al día!')
            ->salutation('Saludos, tu App de Finanzas');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'reminder_id' => $this->reminder->id,
            'title' => $this->reminder->title,
            'description' => $this->reminder->description,
            'amount' => $this->reminder->amount,
            'due_date' => $this->reminder->next_due_date->toDateString(),
            'is_overdue' => $this->reminder->isOverdue(),
            'type' => 'reminder',
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reminder_id' => $this->reminder->id,
            'title' => $this->reminder->title,
            'description' => $this->reminder->description,
            'amount' => $this->reminder->amount,
            'due_date' => $this->reminder->next_due_date->toDateString(),
            'is_overdue' => $this->reminder->isOverdue(),
        ];
    }
}
