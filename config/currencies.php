<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    | The default currency that will be used when no currency is specified.
    | Can be overridden per user or per transaction.
    */
    'default' => env('DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Base Currency
    |--------------------------------------------------------------------------
    | The base currency used for storage in the database.
    | All amounts are converted to this currency before being stored.
    */
    'base' => env('BASE_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    | List of all supported currencies with their symbols and decimal places.
    */
    'supported' => [
        'USD' => [
            'name' => 'US Dollar',
            'symbol' => '$',
            'code' => 'USD',
            'decimal_places' => 2,
        ],
        'BRL' => [
            'name' => 'Real Brasileiro',
            'symbol' => 'R$',
            'code' => 'BRL',
            'decimal_places' => 2,
        ],
        'EUR' => [
            'name' => 'Euro',
            'symbol' => '€',
            'code' => 'EUR',
            'decimal_places' => 2,
        ],
        'GBP' => [
            'name' => 'British Pound',
            'symbol' => '£',
            'code' => 'GBP',
            'decimal_places' => 2,
        ],
        'CAD' => [
            'name' => 'Canadian Dollar',
            'symbol' => 'C$',
            'code' => 'CAD',
            'decimal_places' => 2,
        ],
        'AUD' => [
            'name' => 'Australian Dollar',
            'symbol' => 'A$',
            'code' => 'AUD',
            'decimal_places' => 2,
        ],
        'JPY' => [
            'name' => 'Japanese Yen',
            'symbol' => '¥',
            'code' => 'JPY',
            'decimal_places' => 0,
        ],
        'MXN' => [
            'name' => 'Peso Mexicano',
            'symbol' => '$',
            'code' => 'MXN',
            'decimal_places' => 2,
        ],
        'ARS' => [
            'name' => 'Peso Argentino',
            'symbol' => '$',
            'code' => 'ARS',
            'decimal_places' => 2,
        ],
        'COP' => [
            'name' => 'Peso Colombiano',
            'symbol' => '$',
            'code' => 'COP',
            'decimal_places' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Regional Settings
    |--------------------------------------------------------------------------
    | Default regional settings for currency formatting.
    */
    'regional' => [
        'USD' => ['locale' => 'en-US'],
        'BRL' => ['locale' => 'pt-BR'],
        'EUR' => ['locale' => 'de-DE'],
        'GBP' => ['locale' => 'en-GB'],
        'CAD' => ['locale' => 'en-CA'],
        'AUD' => ['locale' => 'en-AU'],
        'JPY' => ['locale' => 'ja-JP'],
        'MXN' => ['locale' => 'es-MX'],
        'ARS' => ['locale' => 'es-AR'],
        'COP' => ['locale' => 'es-CO'],
    ],
];