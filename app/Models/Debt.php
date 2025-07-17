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
}
