<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_dues', function (Blueprint $table) {
            // Set when the member taps "Beri tahu bendahara" (screen 17)
            // — distinct from an actual recorded payment. Drives
            // "Menunggu konfirmasi" (mobile-screens.md § 16), which is a
            // different state from a partial payment ("Sebagian").
            $table->timestamp('notified_at')->nullable();
            // No screen currently offers an affordance to set this (see
            // ADR — ROADMAP note); added alongside notified_at so the
            // "Dibebaskan" display path isn't half-built once one exists.
            $table->boolean('is_exempt')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('member_dues', function (Blueprint $table) {
            $table->dropColumn(['notified_at', 'is_exempt']);
        });
    }
};
