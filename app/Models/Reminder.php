<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'amount',
        'frequency',
        'next_due_date',
        'start_date',
        'end_date',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'next_due_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate next due date based on frequency
     */
    public function calculateNextDueDate(): Carbon
    {
        $currentDueDate = Carbon::parse($this->next_due_date);

        switch ($this->frequency) {
            case 'daily':
                return $currentDueDate->addDay();
            case 'weekly':
                return $currentDueDate->addWeek();
            case 'monthly':
                return $currentDueDate->addMonth();
            case 'yearly':
                return $currentDueDate->addYear();
            default:
                return $currentDueDate;
        }
    }

    /**
     * Update next due date after reminder is processed
     */
    public function updateNextDueDate(): void
    {
        $this->next_due_date = $this->calculateNextDueDate();
        $this->save();
    }

    /**
     * Check if reminder is due today
     */
    public function isDueToday(): bool
    {
        return $this->next_due_date->isToday();
    }

    /**
     * Check if reminder is overdue
     */
    public function isOverdue(): bool
    {
        return $this->next_due_date->isPast() && !$this->next_due_date->isToday();
    }

    /**
     * Scope for active reminders
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for due reminders
     */
    public function scopeDue($query)
    {
        return $query->where('next_due_date', '<=', now()->toDateString());
    }

    /**
     * Scope for upcoming reminders
     */
    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->whereBetween('next_due_date', [
            now()->toDateString(),
            now()->addDays($days)->toDateString()
        ]);
    }
}
