<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('member_due_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('financial_transaction_id')->nullable()
                ->constrained('financial_transactions')->nullOnDelete();
            $table->bigInteger('amount');
            $table->date('paid_at');
            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE member_payments ADD CONSTRAINT member_payments_amount_positive CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_payments');
    }
};
