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
        // Only add to tables that don't already have currency column
        
        // Add currency field to budgets table
        Schema::table('budgets', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('limit');
            $table->index('currency');
        });

        // Add currency field to goals table
        Schema::table('goals', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('target_amount');
            $table->index('currency');
        });

        // Add currency field to debt_payments table
        Schema::table('debt_payments', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('amount');
            $table->index('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropIndex(['currency']);
            $table->dropColumn('currency');
        });

        Schema::table('goals', function (Blueprint $table) {
            $table->dropIndex(['currency']);
            $table->dropColumn('currency');
        });

        Schema::table('debt_payments', function (Blueprint $table) {
            $table->dropIndex(['currency']);
            $table->dropColumn('currency');
        });
    }
};
