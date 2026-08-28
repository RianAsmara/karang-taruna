<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable, not `$fillable` on the model — only ever set via
            // SwitchOrganizationAction, which verifies real membership
            // first. Null means "no preference yet" — currentMembership()
            // falls back to the first membership, unchanged behavior for
            // every single-org user (the common case).
            $table->foreignUlid('active_organization_id')->nullable()->after('id')->constrained('organizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['active_organization_id']);
            $table->dropColumn('active_organization_id');
        });
    }
};
