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
        Schema::table('events', function (Blueprint $table) {
            $table->string('category')->nullable()->after('description');
            $table->foreignUlid('sponsor_id')->nullable()->after('pic_membership_id')
                ->constrained()->nullOnDelete();
            $table->foreignUlid('budget_financial_transaction_id')->nullable()->after('sponsor_id')
                ->constrained('financial_transactions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['sponsor_id']);
            $table->dropForeign(['budget_financial_transaction_id']);
            $table->dropColumn(['sponsor_id', 'budget_financial_transaction_id', 'category']);
        });
    }
};
