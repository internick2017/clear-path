<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TransactionCategoryChangeNotification extends Notification
{
    use Queueable;

    /**
     * The transaction instance
     * 
     * @var Transaction
     */
    protected $transaction;

    /**
     * The previous category
     * 
     * @var string
     */
    protected $previousCategory;

    /**
     * Create a new notification instance.
     * 
     * @param Transaction $transaction
     * @param string $previousCategory
     */
    public function __construct(Transaction $transaction, string $previousCategory)
    {
        $this->transaction = $transaction;
        $this->previousCategory = $previousCategory;
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
            ->subject('Transaction Category Changed')
            ->line("A transaction has been recategorized.")
            ->line("Transaction Amount: $" . number_format($this->transaction->amount, 2))
            ->line("Previous Category: {$this->previousCategory}")
            ->line("New Category: {$this->transaction->category}")
            ->action('View Transaction', route('transactions.index'))
            ->line('Please review this change and update your budget tracking if necessary.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amount,
            'previous_category' => $this->previousCategory,
            'new_category' => $this->transaction->category,
            'date' => $this->transaction->date,
        ];
    }
} 