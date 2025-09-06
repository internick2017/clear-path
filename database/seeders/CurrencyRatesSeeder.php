<?php

namespace Database\Seeders;

use App\Services\CurrencyConversionService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencyRatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CurrencyConversionService::seedInitialRates();
    }
}
