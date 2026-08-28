<?php

use App\Enums\AttendanceMethod;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('membership_id')->constrained('organization_memberships')->cascadeOnDelete();
            $table->string('status')->default(AttendanceStatus::Hadir->value);
            $table->string('method')->default(AttendanceMethod::Manual->value);
            $table->timestamp('checked_in_at');
            $table->timestamps();

            // Prevents duplicate attendance for the same member+session —
            // master prompt §31.
            $table->unique(['attendance_session_id', 'membership_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
