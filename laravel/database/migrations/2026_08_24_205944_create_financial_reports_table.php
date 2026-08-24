<?php

use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('report_type');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default(FinancialReportStatus::Draft->value);
            $table->string('visibility')->default(FinancialReportVisibility::Members->value);
            $table->bigInteger('opening_balance')->default(0);
            $table->bigInteger('total_income')->default(0);
            $table->bigInteger('total_expense')->default(0);
            $table->bigInteger('closing_balance')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignUlid('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};
