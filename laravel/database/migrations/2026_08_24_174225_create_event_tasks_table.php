<?php

use App\Enums\EventTaskPriority;
use App\Enums\EventTaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('event_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUlid('assignee_membership_id')->nullable()
                ->constrained('organization_memberships')->nullOnDelete();
            $table->string('status')->default(EventTaskStatus::Todo->value);
            $table->string('priority')->default(EventTaskPriority::Medium->value);
            $table->date('due_date')->nullable();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_tasks');
    }
};
