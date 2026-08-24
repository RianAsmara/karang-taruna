<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('transaction_type');
            $table->timestamps();

            $table->unique(['organization_id', 'name', 'transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_categories');
    }
};
