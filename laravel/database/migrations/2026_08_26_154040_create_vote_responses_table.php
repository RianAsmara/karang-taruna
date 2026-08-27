<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `membership_id` is always stored, even for anonymous votes — an
     * editable anonymous vote needs it to find and replace a member's
     * prior selection when they change their mind. Anonymity is enforced
     * entirely at the read/API layer (no response ever joins this back
     * to an identity for an anonymous vote, for any viewer including the
     * chair) — see VotePolicy and VoteResource.
     */
    public function up(): void
    {
        Schema::create('vote_responses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('vote_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('vote_option_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('membership_id')->constrained('organization_memberships')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['vote_id', 'membership_id', 'vote_option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_responses');
    }
};
