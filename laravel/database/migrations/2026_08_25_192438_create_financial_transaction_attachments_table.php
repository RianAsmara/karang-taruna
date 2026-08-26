<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transaction_attachments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('financial_transaction_id')->constrained()->cascadeOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedInteger('size_bytes');
            $table->foreignUlid('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('financial_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transaction_attachments');
    }
};
