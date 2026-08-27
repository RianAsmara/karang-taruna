<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsors');
    }
};
