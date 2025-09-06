<?php

namespace App\Models;

use App\Helpers\CurrencyHelper;
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
        'total_amount',
        'amount_paid',
        'original_amount',
        'currency',
        'interest_rate',
        'minimum_payment',
        'due_date',
        'strategy',
        'status',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'original_amount' => 'decimal:2',
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
            'currency' => $this->currency,
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
            'currency' => $this->currency,
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
            'currency' => $this->currency,
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

    /**
     * Get formatted amount in user's preferred currency
     */
    public function getFormattedAmountAttribute()
    {
        $userCurrency = CurrencyHelper::getUserCurrency();
        return CurrencyHelper::formatStoredAmount($this->amount, $userCurrency);
    }

    /**
     * Get formatted minimum payment in user's preferred currency
     */
    public function getFormattedMinimumPaymentAttribute()
    {
        $userCurrency = CurrencyHelper::getUserCurrency();
        return CurrencyHelper::formatStoredAmount($this->minimum_payment, $userCurrency);
    }

    /**
     * Convert input amount to base currency before saving
     */
    public function setAmountAttribute($value)
    {
        // Convert from input currency to base currency for storage
        if ($this->currency && $this->currency !== config('currencies.base', 'USD')) {
            $this->attributes['amount'] = CurrencyHelper::convertToBaseAmount($value, $this->currency);
        } else {
            $this->attributes['amount'] = $value;
        }
    }

    /**
     * Convert input minimum payment to base currency before saving
     */
    public function setMinimumPaymentAttribute($value)
    {
        // Convert from input currency to base currency for storage
        if ($this->currency && $this->currency !== config('currencies.base', 'USD')) {
            $this->attributes['minimum_payment'] = CurrencyHelper::convertToBaseAmount($value, $this->currency);
        } else {
            $this->attributes['minimum_payment'] = $value;
        }
    }

    /**
     * Calculate missing field based on available data
     * Requires at least 3 of 4 fields: original_amount, total_amount, interest_rate, minimum_payment
     */
    public static function calculateMissingField(array $data): array
    {
        $fields = [
            'original_amount' => $data['original_amount'] ?? null,
            'total_amount' => $data['total_amount'] ?? null,
            'interest_rate' => $data['interest_rate'] ?? null,
            'minimum_payment' => $data['minimum_payment'] ?? null,
        ];

        // Count non-null fields
        $providedFields = array_filter($fields, fn($value) => $value !== null && $value !== '');
        
        if (count($providedFields) < 3) {
            throw new \InvalidArgumentException('At least 3 of 4 fields are required: original_amount, total_amount, interest_rate, minimum_payment');
        }

        // Calculate missing field
        if ($fields['original_amount'] === null) {
            // Calculate original amount from total amount and interest
            if ($fields['total_amount'] && $fields['interest_rate']) {
                // Simplified calculation: assuming total_amount includes all interest
                $totalInterest = $fields['total_amount'] * ($fields['interest_rate'] / 100);
                $fields['original_amount'] = $fields['total_amount'] - $totalInterest;
            }
        }

        if ($fields['total_amount'] === null) {
            // Calculate total amount from original amount and interest
            if ($fields['original_amount'] && $fields['interest_rate']) {
                // Simplified calculation for demonstration
                $totalInterest = $fields['original_amount'] * ($fields['interest_rate'] / 100);
                $fields['total_amount'] = $fields['original_amount'] + $totalInterest;
            }
        }

        if ($fields['interest_rate'] === null) {
            // Calculate interest rate from original and total amounts
            if ($fields['original_amount'] && $fields['total_amount'] && $fields['original_amount'] > 0) {
                $interest = $fields['total_amount'] - $fields['original_amount'];
                $fields['interest_rate'] = ($interest / $fields['original_amount']) * 100;
            }
        }

        if ($fields['minimum_payment'] === null) {
            // Estimate minimum payment (simplified - could be more sophisticated)
            if ($fields['total_amount']) {
                // Assume 24 months payment term as default
                $fields['minimum_payment'] = $fields['total_amount'] / 24;
            }
        }

        return array_merge($data, $fields);
    }

    /**
     * Validate that provided fields are mathematically consistent
     */
    public static function validateDebtCalculations(array $data): bool
    {
        $original = $data['original_amount'] ?? 0;
        $total = $data['total_amount'] ?? 0;
        $rate = $data['interest_rate'] ?? 0;
        $payment = $data['minimum_payment'] ?? 0;

        // Basic validation rules
        if ($total > 0 && $original > 0 && $total < $original) {
            return false; // Total should be >= original
        }

        if ($rate < 0 || $rate > 100) {
            return false; // Interest rate should be reasonable
        }

        if ($payment < 0) {
            return false; // Payment can't be negative
        }

        return true;
    }
}
