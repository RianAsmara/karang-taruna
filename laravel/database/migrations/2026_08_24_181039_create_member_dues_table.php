<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_dues', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('membership_id')->constrained('organization_memberships')->cascadeOnDelete();
            $table->date('period');
            $table->bigInteger('amount_due');
            $table->string('type');
            $table->timestamps();

            $table->unique(['organization_id', 'membership_id', 'period', 'type']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE member_dues ADD CONSTRAINT member_dues_amount_due_positive CHECK (amount_due > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_dues');
    }
};
