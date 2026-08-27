<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform-level, not tenant-scoped — deliberately independent of
 * OrganizationMembership (see docs/decisions.md ADR on superadmin).
 * Never mass-assignable (absent from User::$fillable) and never
 * settable through any HTTP endpoint — the only way to grant/revoke it
 * is `php artisan superadmin:grant|revoke {email}`, a human running a
 * command on the server, not a client-facing action.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_superadmin')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_superadmin');
        });
    }
};
