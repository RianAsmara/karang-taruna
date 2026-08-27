<?php

use App\Enums\VoteEligibleScope;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->text('description')->nullable();
            $table->boolean('anonymous')->default(false);
            $table->boolean('editable')->default(true);
            $table->unsignedTinyInteger('max_selections')->default(1);
            $table->string('eligible_scope')->default(VoteEligibleScope::All->value);
            $table->foreignUlid('event_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
