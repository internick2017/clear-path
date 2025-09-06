<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Debt extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'name',
        'amount',
        'interest_rate',
        'minimum_payment',
        'due_date',
        'strategy',
        'status',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'minimum_payment' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function addPayment(float $amount, string $paymentDate, string $paymentMethod = null, string $note = null)
    {
        // Calculate interest and principal portions
        $interestAmount = $this->getRemainingBalance() * ($this->interest_rate / 100 / 12);
        $principalAmount = max(0, $amount - $interestAmount);

        $payment = $this->payments()->create([
            'user_id' => $this->user_id,
            'amount' => $amount,
            'principal_amount' => $principalAmount,
            'interest_amount' => min($amount, $interestAmount),
            'payment_date' => $paymentDate,
            'payment_method' => $paymentMethod,
            'note' => $note,
        ]);

        // Check if debt is fully paid
        if ($this->getRemainingBalance() <= 0) {
            $this->markAsPaid();
        }

        return $payment;
    }

    public function createPaymentTransaction(float $amount, string $paymentDate, string $paymentMethod = null, string $note = null)
    {
        // Create the transaction record
        $transaction = $this->transactions()->create([
            'user_id' => $this->user_id,
            'type' => 'expense',
            'category' => 'Debt Payment',
            'amount' => $amount,
            'date' => $paymentDate,
            'note' => $note ? "Pago de deuda: {$this->name}. {$note}" : "Pago de deuda: {$this->name}",
            'expense_type' => 'fixed'
        ]);

        // Create the debt payment record
        $payment = $this->addPayment($amount, $paymentDate, $paymentMethod, $note);

        return [
            'transaction' => $transaction,
            'payment' => $payment
        ];
    }

    public function addPurchase(float $amount, string $purchaseDate, string $category, string $note = null)
    {
        // Create the transaction record
        $transaction = $this->transactions()->create([
            'user_id' => $this->user_id,
            'type' => 'expense',
            'category' => $category,
            'amount' => $amount,
            'date' => $purchaseDate,
            'note' => $note ? "Compra con {$this->name}: {$note}" : "Compra con {$this->name}",
            'expense_type' => 'variable'
        ]);

        // Increase the debt amount
        $this->increment('amount', $amount);

        // If debt was marked as paid, reactivate it
        if ($this->status === 'paid') {
            $this->markAsActive();
        }

        return $transaction;
    }

    public function getRemainingBalance(): float
    {
        return $this->amount - $this->getTotalPaidAmount();
    }

    public function getTotalPaidAmount(): float
    {
        return $this->payments()->sum('amount');
    }

    public function getTotalPrincipalPaid(): float
    {
        return $this->payments()->sum('principal_amount');
    }

    public function getTotalInterestPaid(): float
    {
        return $this->payments()->sum('interest_amount');
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);
    }

    public function markAsActive()
    {
        $this->update([
            'status' => 'active',
            'paid_at' => null
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function getIsPaidAttribute()
    {
        return $this->status === 'paid';
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    public function getPaymentProgressAttribute()
    {
        if ($this->amount <= 0) {
            return 100;
        }
        
        return min(100, ($this->getTotalPaidAmount() / $this->amount) * 100);
    }
}
