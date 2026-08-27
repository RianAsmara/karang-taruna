<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            // Opt-in, per mobile-ux.md § Open product decisions: "Whether
            // members may see each other's phone numbers by default, or
            // only after opting in. The design assumes opt-in." Pengurus
            // can always see it regardless — enforced in MemberResource,
            // not this column.
            $table->boolean('show_phone_to_members')->default(false)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'show_phone_to_members']);
        });
    }
};
