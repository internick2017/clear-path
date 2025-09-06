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
        Schema::table('debts', function (Blueprint $table) {
            // Add new flexible fields
            $table->decimal('total_amount', 15, 2)->nullable()->after('amount')->comment('Total amount to be paid (capital + interest)');
            $table->decimal('amount_paid', 15, 2)->default(0)->after('total_amount')->comment('Amount already paid before registration');
            $table->decimal('original_amount', 15, 2)->nullable()->after('amount_paid')->comment('Original capital amount (calculated if not provided)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn(['total_amount', 'amount_paid', 'original_amount']);
        });
    }
};
