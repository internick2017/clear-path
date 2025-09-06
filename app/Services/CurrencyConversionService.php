<?php

namespace App\Services;

use App\Models\CurrencyRate;
use Carbon\Carbon;

class CurrencyConversionService
{
    const BASE_CURRENCY = 'USD'; // All amounts stored in USD

    /**
     * Convert amount from user's input currency to base currency for storage
     */
    public static function toBaseCurrency(float $amount, string $fromCurrency): float
    {
        if ($fromCurrency === self::BASE_CURRENCY) {
            return $amount;
        }

        $rate = CurrencyRate::getRate($fromCurrency, self::BASE_CURRENCY);
        
        if (!$rate) {
            // If no rate found, store as-is and log warning
            \Log::warning("No conversion rate found from {$fromCurrency} to " . self::BASE_CURRENCY);
            return $amount;
        }

        return $amount * $rate;
    }

    /**
     * Convert amount from base currency to user's display currency
     */
    public static function fromBaseCurrency(float $amount, string $toCurrency): float
    {
        if ($toCurrency === self::BASE_CURRENCY) {
            return $amount;
        }

        $rate = CurrencyRate::getRate(self::BASE_CURRENCY, $toCurrency);
        
        if (!$rate) {
            // If no rate found, return as-is and log warning
            \Log::warning("No conversion rate found from " . self::BASE_CURRENCY . " to {$toCurrency}");
            return $amount;
        }

        return $amount * $rate;
    }

    /**
     * Convert between any two currencies
     */
    public static function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        // Convert to base currency first, then to target currency
        $baseAmount = self::toBaseCurrency($amount, $fromCurrency);
        return self::fromBaseCurrency($baseAmount, $toCurrency);
    }

    /**
     * Seed initial exchange rates (approximate values for development)
     */
    public static function seedInitialRates(): void
    {
        $rates = [
            // USD to other currencies
            ['USD', 'BRL', 5.20],   // 1 USD = 5.20 BRL
            ['USD', 'EUR', 0.85],   // 1 USD = 0.85 EUR
            ['USD', 'GBP', 0.73],   // 1 USD = 0.73 GBP
            ['USD', 'CAD', 1.25],   // 1 USD = 1.25 CAD
            ['USD', 'AUD', 1.35],   // 1 USD = 1.35 AUD
            ['USD', 'JPY', 110.0],  // 1 USD = 110 JPY
            ['USD', 'MXN', 18.5],   // 1 USD = 18.5 MXN
            ['USD', 'ARS', 350.0],  // 1 USD = 350 ARS
            ['USD', 'COP', 4000.0], // 1 USD = 4000 COP
            
            // Reverse rates (other currencies to USD)
            ['BRL', 'USD', 1/5.20],
            ['EUR', 'USD', 1/0.85],
            ['GBP', 'USD', 1/0.73],
            ['CAD', 'USD', 1/1.25],
            ['AUD', 'USD', 1/1.35],
            ['JPY', 'USD', 1/110.0],
            ['MXN', 'USD', 1/18.5],
            ['ARS', 'USD', 1/350.0],
            ['COP', 'USD', 1/4000.0],
        ];

        foreach ($rates as [$from, $to, $rate]) {
            CurrencyRate::setRate($from, $to, $rate);
        }
    }

    /**
     * Get all supported currencies with current rates to base currency
     */
    public static function getSupportedCurrenciesWithRates(): array
    {
        $currencies = config('currencies.supported', []);
        $result = [];

        foreach ($currencies as $code => $config) {
            $rate = null;
            if ($code !== self::BASE_CURRENCY) {
                $rate = CurrencyRate::getRate(self::BASE_CURRENCY, $code);
            } else {
                $rate = 1.0; // Base currency
            }

            $result[$code] = array_merge($config, [
                'rate_from_base' => $rate,
                'is_base_currency' => $code === self::BASE_CURRENCY
            ]);
        }

        return $result;
    }
}