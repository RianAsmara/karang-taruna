<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // One session per event — this app has no notion of a
            // multi-part event with several separate check-in windows.
            $table->foreignUlid('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('qr_token', 40)->unique();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
