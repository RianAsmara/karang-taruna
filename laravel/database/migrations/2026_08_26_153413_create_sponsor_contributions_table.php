<?php

use App\Enums\SponsorContributionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsor_contributions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('sponsor_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('status')->default(SponsorContributionStatus::Diajukan->value);
            $table->bigInteger('amount')->nullable();
            $table->text('description')->nullable();
            $table->foreignUlid('financial_transaction_id')->nullable()
                ->constrained('financial_transactions')->nullOnDelete();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE sponsor_contributions ADD CONSTRAINT sponsor_contributions_amount_positive CHECK (amount IS NULL OR amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsor_contributions');
    }
};
