<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class ReminderService
{
    /**
     * Create a new reminder
     */
    public static function createReminder(array $data): Reminder
    {
        $reminder = Reminder::create($data);

        // Schedule initial notification if due soon
        if ($reminder->isDueToday() || $reminder->next_due_date->diffInDays(now()) <= 3) {
            self::sendReminderNotification($reminder);
        }

        return $reminder;
    }

    /**
     * Process daily reminders
     */
    public static function processDailyReminders(): void
    {
        $dueReminders = Reminder::active()
            ->where('next_due_date', '<=', now()->toDateString())
            ->get();

        foreach ($dueReminders as $reminder) {
            // Send notification
            self::sendReminderNotification($reminder);

            // Update next due date
            $reminder->updateNextDueDate();
        }
    }

    /**
     * Send reminder notification
     */
    private static function sendReminderNotification(Reminder $reminder): void
    {
        Notification::send($reminder->user, new ReminderNotification($reminder));
    }

    /**
     * Get upcoming reminders for user
     */
    public static function getUpcomingReminders(User $user, int $days = 7): array
    {
        return $user->reminders()
            ->active()
            ->upcoming($days)
            ->orderBy('next_due_date')
            ->get()
            ->map(function ($reminder) {
                return [
                    'id' => $reminder->id,
                    'title' => $reminder->title,
                    'description' => $reminder->description,
                    'amount' => $reminder->amount,
                    'next_due_date' => $reminder->next_due_date,
                    'days_until_due' => now()->diffInDays($reminder->next_due_date, false),
                    'frequency' => $reminder->frequency,
                    'is_overdue' => $reminder->isOverdue(),
                ];
            })
            ->toArray();
    }

    /**
     * Create common reminders for new users
     */
    public static function createDefaultReminders(User $user): void
    {
        $defaultReminders = [
            [
                'title' => 'Pago de Renta',
                'description' => 'Pago mensual de alquiler o hipoteca',
                'frequency' => 'monthly',
                'next_due_date' => now()->addDays(5)->toDateString(),
            ],
            [
                'title' => 'Servicios Públicos',
                'description' => 'Pago de luz, agua, gas e internet',
                'frequency' => 'monthly',
                'next_due_date' => now()->addDays(10)->toDateString(),
            ],
            [
                'title' => 'Pago de Tarjeta de Crédito',
                'description' => 'Pago mínimo de tarjeta de crédito',
                'frequency' => 'monthly',
                'next_due_date' => now()->addDays(15)->toDateString(),
            ],
            [
                'title' => 'Seguro de Auto',
                'description' => 'Pago de póliza de seguro vehicular',
                'frequency' => 'yearly',
                'next_due_date' => now()->addMonths(6)->toDateString(),
            ],
        ];

        foreach ($defaultReminders as $reminderData) {
            $user->reminders()->create($reminderData);
        }
    }

    /**
     * Auto-create reminders from transaction patterns
     */
    public static function createRemindersFromPatterns(User $user): void
    {
        // Find recurring transactions
        $recurringTransactions = $user->transactions()
            ->selectRaw('category, COUNT(*) as transaction_count, AVG(amount) as avg_amount')
            ->where('type', 'expense')
            ->where('expense_type', 'fixed')
            ->where('date', '>=', now()->subMonths(3))
            ->groupBy('category')
            ->having('transaction_count', '>=', 2)
            ->get();

        foreach ($recurringTransactions as $transaction) {
            // Check if reminder already exists
            $existingReminder = $user->reminders()
                ->where('title', 'like', '%' . $transaction->category . '%')
                ->first();

            if (!$existingReminder) {
                $user->reminders()->create([
                    'title' => 'Pago recurrente: ' . $transaction->category,
                    'description' => 'Pago automático detectado para ' . $transaction->category,
                    'amount' => $transaction->avg_amount,
                    'frequency' => 'monthly',
                    'next_due_date' => now()->addMonth()->toDateString(),
                    'metadata' => [
                        'auto_generated' => true,
                        'source' => 'pattern_analysis',
                        'category' => $transaction->category
                    ]
                ]);
            }
        }
    }
}
