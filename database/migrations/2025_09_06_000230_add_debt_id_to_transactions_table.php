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
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('debt_id')->nullable()->after('expense_type');
            $table->foreign('debt_id')->references('id')->on('debts')->onDelete('set null');
            $table->index('debt_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['debt_id']);
            $table->dropIndex(['debt_id']);
            $table->dropColumn('debt_id');
        });
    }
};
