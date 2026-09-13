<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Leaving an organization is a request, not a unilateral act: the
     * chair approves or rejects it (the member's dues history and
     * transactions stay attached to the organization's records either
     * way). Approval soft-deletes the membership, reusing the existing
     * 30-day "Keluar" retention window rather than inventing a second
     * departure mechanism alongside the chair's own "Keluarkan".
     */
    public function up(): void
    {
        Schema::create('membership_exit_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('membership_id')->constrained('organization_memberships')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->string('status');
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        // At most one undecided request per membership — enforced in the
        // database, not just in the Action, so a double-tapped submit
        // can't queue two requests for the chair to decide on. A partial
        // index is the right shape here: a member who was rejected once
        // must still be able to ask again.
        DB::statement('CREATE UNIQUE INDEX membership_exit_requests_one_pending_per_membership ON membership_exit_requests (membership_id) WHERE status = \'PENDING\'');
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_exit_requests');
    }
};
