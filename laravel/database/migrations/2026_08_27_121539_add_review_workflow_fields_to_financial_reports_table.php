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
        Schema::table('financial_reports', function (Blueprint $table) {
            $table->text('treasurer_note')->nullable()->after('created_by');
            $table->text('revision_reason')->nullable()->after('treasurer_note');
            $table->timestamp('submitted_at')->nullable()->after('revision_reason');
            $table->foreignUlid('submitted_by')->nullable()->after('submitted_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('submitted_by');
            $table->foreignUlid('approved_by')->nullable()->after('approved_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_reports', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['treasurer_note', 'revision_reason', 'submitted_at', 'submitted_by', 'approved_at', 'approved_by']);
        });
    }
};
