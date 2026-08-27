<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Justified soft delete (CLAUDE.md: only where a business requirement
 * needs it) — mobile-screens.md § 12 edge case: "Members who left show
 * a 'Keluar' outline tag for 30 days, then drop off," and
 * mobile-ux.md's retention note keeps their historical transactions and
 * dues intact. A hard delete would either cascade-orphan that history
 * or require nulling foreign keys; soft delete keeps the row (and every
 * FK pointing at it) exactly as it was.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_memberships', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('organization_memberships', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
