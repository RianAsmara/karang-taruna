<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_report_revisions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('financial_report_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->jsonb('snapshot');
            $table->foreignUlid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['financial_report_id', 'revision_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_report_revisions');
    }
};
