<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category');
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedInteger('size_bytes');
            $table->foreignUlid('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
