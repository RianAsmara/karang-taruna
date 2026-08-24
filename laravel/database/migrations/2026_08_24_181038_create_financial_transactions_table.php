<?php

use App\Enums\TransactionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('financial_account_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('related_account_id')->nullable()
                ->constrained('financial_accounts')->restrictOnDelete();
            $table->foreignUlid('category_id')->nullable()
                ->constrained('financial_categories')->restrictOnDelete();
            $table->foreignUlid('event_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('amount');
            $table->string('transaction_type');
            $table->string('status')->default(TransactionStatus::Draft->value);
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->foreignUlid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'financial_account_id', 'status']);
            $table->index(['organization_id', 'transaction_date']);
            $table->index(['organization_id', 'event_id']);
        });

        // SQLite only accepts CHECK constraints inline in CREATE TABLE, not via
        // ALTER TABLE — the constraint is a PostgreSQL-only safety net here;
        // application-level validation (min:1 on amount) covers every driver.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE financial_transactions ADD CONSTRAINT financial_transactions_amount_positive CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
