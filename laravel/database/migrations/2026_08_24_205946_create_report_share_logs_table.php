<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_share_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('financial_report_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->foreignUlid('shared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('shared_at')->useCurrent();

            $table->index(['financial_report_id', 'shared_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_share_logs');
    }
};
