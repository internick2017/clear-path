<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency', 
        'rate',
        'effective_date'
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'effective_date' => 'date'
    ];

    /**
     * Get the latest exchange rate between two currencies
     */
    public static function getRate(string $fromCurrency, string $toCurrency, ?Carbon $date = null): ?float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $date = $date ?? now()->toDateString();

        $rate = static::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();

        return $rate ? (float) $rate->rate : null;
    }

    /**
     * Convert amount from one currency to another
     */
    public static function convert(float $amount, string $fromCurrency, string $toCurrency, ?Carbon $date = null): ?float
    {
        $rate = static::getRate($fromCurrency, $toCurrency, $date);
        
        return $rate ? $amount * $rate : null;
    }

    /**
     * Set exchange rate for a currency pair
     */
    public static function setRate(string $fromCurrency, string $toCurrency, float $rate, ?Carbon $date = null): static
    {
        $date = $date ?? now()->toDate();

        return static::updateOrCreate(
            [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'effective_date' => $date
            ],
            ['rate' => $rate]
        );
    }
}
