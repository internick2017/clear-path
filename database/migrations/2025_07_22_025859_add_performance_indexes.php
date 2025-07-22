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
        // Add indexes to transactions table for common queries
        Schema::table('transactions', function (Blueprint $table) {
            // Index for user_id + date queries (dashboard, monthly summaries)
            $table->index(['user_id', 'date']);
            
            // Index for user_id + type + date queries (income/expense filtering)
            $table->index(['user_id', 'type', 'date']);
            
            // Index for user_id + category queries (budget calculations)
            $table->index(['user_id', 'category']);
            
            // Index for user_id + type + category queries (expense categories)
            $table->index(['user_id', 'type', 'category']);
        });

        // Add indexes to budgets table
        Schema::table('budgets', function (Blueprint $table) {
            // Index for user_id + month queries (monthly budgets)
            $table->index(['user_id', 'month']);
            
            // Index for user_id + category + month queries (unique budget per category per month)
            $table->unique(['user_id', 'category', 'month']);
        });

        // Add indexes to goals table
        Schema::table('goals', function (Blueprint $table) {
            // Index for user_id + deadline queries (active goals)
            $table->index(['user_id', 'deadline']);
            
            // Index for user_id + current_amount + target_amount queries (goal progress)
            $table->index(['user_id', 'current_amount', 'target_amount']);
        });

        // Add indexes to debts table
        Schema::table('debts', function (Blueprint $table) {
            // Index for user_id + status queries (active/paid debts)
            $table->index(['user_id', 'status']);
            
            // Index for user_id + due_date queries (debt planning)
            $table->index(['user_id', 'due_date']);
        });

        // Add indexes to debt_payments table
        Schema::table('debt_payments', function (Blueprint $table) {
            // Index for debt_id + payment_date queries (payment history)
            $table->index(['debt_id', 'payment_date']);
            
            // Index for user_id + payment_date queries (user payment history)
            $table->index(['user_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'date']);
            $table->dropIndex(['user_id', 'type', 'date']);
            $table->dropIndex(['user_id', 'category']);
            $table->dropIndex(['user_id', 'type', 'category']);
        });

        // Remove indexes from budgets table
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'month']);
            $table->dropUnique(['user_id', 'category', 'month']);
        });

        // Remove indexes from goals table
        Schema::table('goals', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'deadline']);
            $table->dropIndex(['user_id', 'current_amount', 'target_amount']);
        });

        // Remove indexes from debts table
        Schema::table('debts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['user_id', 'due_date']);
        });

        // Remove indexes from debt_payments table
        Schema::table('debt_payments', function (Blueprint $table) {
            $table->dropIndex(['debt_id', 'payment_date']);
            $table->dropIndex(['user_id', 'payment_date']);
        });
    }
};
