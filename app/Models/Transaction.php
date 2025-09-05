<?php

namespace App\Models;

use App\Services\BudgetService;
use App\Services\GoalTrackingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id', 'type', 'category', 'amount', 'date', 'note', 'expense_type'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::created(function (Transaction $transaction) {
            $budgetService = app(BudgetService::class);
            $budgetService->handleTransactionCreated($transaction);
            
            $goalService = app(GoalTrackingService::class);
            $goalService->handleTransactionCreated($transaction);
        });

        static::updated(function (Transaction $transaction) {
            $budgetService = app(BudgetService::class);
            $budgetService->handleTransactionUpdated($transaction, $transaction->getOriginal());
            
            $goalService = app(GoalTrackingService::class);
            $goalService->handleTransactionUpdated($transaction, $transaction->getOriginal());
        });

        static::deleted(function (Transaction $transaction) {
            $budgetService = app(BudgetService::class);
            $budgetService->handleTransactionDeleted($transaction);
            
            $goalService = app(GoalTrackingService::class);
            $goalService->handleTransactionDeleted($transaction);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
