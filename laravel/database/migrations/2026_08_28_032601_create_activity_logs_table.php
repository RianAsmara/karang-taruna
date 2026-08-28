<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('membership_id')->constrained('organization_memberships')->cascadeOnDelete();
            $table->unsignedInteger('points');
            $table->string('source');
            // A string reference to the source row (Attendance/EventTask id)
            // rather than a polymorphic relation — this table only ever
            // needs to answer "has this specific action already been
            // awarded?", never to load the related model back.
            $table->string('source_id');
            $table->string('description')->nullable();
            $table->timestamps();

            // A task can be toggled DONE -> TODO -> DONE repeatedly;
            // this is what actually prevents re-awarding points for the
            // same underlying action, not application-level care alone.
            $table->unique(['source', 'source_id']);
            $table->index(['organization_id', 'membership_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
