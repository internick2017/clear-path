<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3); // Base currency (USD)
            $table->string('to_currency', 3);   // Target currency 
            $table->decimal('rate', 15, 8);     // Exchange rate with high precision
            $table->date('effective_date');     // Date when rate is effective
            $table->timestamps();
            
            // Ensure unique combination of currencies per date
            $table->unique(['from_currency', 'to_currency', 'effective_date']);
            $table->index(['to_currency', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
