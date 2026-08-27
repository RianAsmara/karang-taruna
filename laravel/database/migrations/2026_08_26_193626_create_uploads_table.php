<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A general-purpose file store (jpg/jpeg/png/pdf) backed by the app's
 * default disk (MinIO/S3 in every environment — see config/filesystems.php
 * and docker-compose.yml). Distinct from FinancialTransactionAttachment
 * and Document, which stay domain-specific (they carry their own foreign
 * keys and authorization rules); this table is for callers that just need
 * "store a file, get a stable authorized URL back" without a domain tie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedInteger('size_bytes');
            $table->foreignUlid('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
