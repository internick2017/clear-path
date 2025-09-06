<?php

namespace App\Helpers;

use App\Services\CurrencyConversionService;

class CurrencyHelper
{
    /**
     * Format currency with proper symbol and decimal places
     */
    public static function format(float $amount, string $currency = null): string
    {
        $currency = $currency ?: config('currencies.default', 'USD');
        $currencyConfig = config("currencies.supported.{$currency}");
        
        if (!$currencyConfig) {
            // Fallback to USD if currency not found
            $currencyConfig = config('currencies.supported.USD');
            $currency = 'USD';
        }

        $symbol = $currencyConfig['symbol'];
        $decimalPlaces = $currencyConfig['decimal_places'];
        
        // Use correct decimal and thousands separators based on currency
        $decimalSep = self::getDecimalSeparator($currency);
        $thousandsSep = self::getThousandsSeparator($currency);
        
        return $symbol . number_format($amount, $decimalPlaces, $decimalSep, $thousandsSep);
    }

    /**
     * Format currency for Vue components (returns object with symbol and formatted amount)
     */
    public static function formatForVue(float $amount, string $currency = null): array
    {
        $currency = $currency ?: config('currencies.default', 'USD');
        $currencyConfig = config("currencies.supported.{$currency}");
        
        if (!$currencyConfig) {
            $currencyConfig = config('currencies.supported.USD');
            $currency = 'USD';
        }

        return [
            'symbol' => $currencyConfig['symbol'],
            'amount' => number_format($amount, $currencyConfig['decimal_places'], self::getDecimalSeparator($currency), self::getThousandsSeparator($currency)),
            'formatted' => self::format($amount, $currency),
            'code' => $currency,
        ];
    }

    /**
     * Get all supported currencies for form options
     */
    public static function getSupportedCurrencies(): array
    {
        $currencies = config('currencies.supported', []);
        $options = [];
        
        foreach ($currencies as $code => $config) {
            $options[$code] = [
                'code' => $code,
                'name' => $config['name'],
                'symbol' => $config['symbol'],
                'display' => "{$code} - {$config['name']} ({$config['symbol']})",
            ];
        }
        
        return $options;
    }

    /**
     * Get currency symbol by code
     */
    public static function getSymbol(string $currency): string
    {
        return config("currencies.supported.{$currency}.symbol", '$');
    }

    /**
     * Check if currency is supported
     */
    public static function isSupported(string $currency): bool
    {
        return array_key_exists($currency, config('currencies.supported', []));
    }

    /**
     * Get decimal separator for currency
     */
    public static function getDecimalSeparator(string $currency): string
    {
        // Brazilian Real and most European currencies use comma for decimal
        $commaDecimalCurrencies = ['BRL', 'EUR', 'ARS', 'COP'];
        
        return in_array($currency, $commaDecimalCurrencies) ? ',' : '.';
    }

    /**
     * Get thousands separator for currency
     */
    public static function getThousandsSeparator(string $currency): string
    {
        // Brazilian Real and most European currencies use period for thousands
        $periodThousandsCurrencies = ['BRL', 'EUR', 'ARS', 'COP'];
        
        return in_array($currency, $periodThousandsCurrencies) ? '.' : ',';
    }

    /**
     * Format amount stored in base currency for display in user's preferred currency
     */
    public static function formatStoredAmount(float $baseAmount, string $displayCurrency = null): string
    {
        $displayCurrency = $displayCurrency ?: config('currencies.default', 'USD');
        $baseCurrency = config('currencies.base', 'USD');
        
        // Convert from base currency to display currency
        $displayAmount = CurrencyConversionService::fromBaseCurrency($baseAmount, $displayCurrency);
        
        return self::format($displayAmount, $displayCurrency);
    }

    /**
     * Convert user input amount to base currency for storage
     */
    public static function convertToBaseAmount(float $amount, string $fromCurrency): float
    {
        return CurrencyConversionService::toBaseCurrency($amount, $fromCurrency);
    }

    /**
     * Format stored amount for Vue components with currency conversion
     */
    public static function formatStoredForVue(float $baseAmount, string $displayCurrency = null): array
    {
        $displayCurrency = $displayCurrency ?: config('currencies.default', 'USD');
        $baseCurrency = config('currencies.base', 'USD');
        
        // Convert from base currency to display currency
        $displayAmount = CurrencyConversionService::fromBaseCurrency($baseAmount, $displayCurrency);
        
        return self::formatForVue($displayAmount, $displayCurrency);
    }

    /**
     * Get user's preferred currency from auth user or default
     */
    public static function getUserCurrency(): string
    {
        $user = auth()->user();
        
        if ($user && $user->display_currency) {
            return $user->display_currency;
        }
        
        return config('currencies.default', 'USD');
    }

    /**
     * Convert stored amount (in base currency) to user's display currency without formatting
     */
    public static function convertStoredAmount(float $baseAmount, string $displayCurrency = null): float
    {
        $displayCurrency = $displayCurrency ?: self::getUserCurrency();
        $baseCurrency = config('currencies.base', 'USD');
        
        // Convert from base currency to display currency
        return CurrencyConversionService::fromBaseCurrency($baseAmount, $displayCurrency);
    }
}