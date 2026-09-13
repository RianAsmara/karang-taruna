<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shareable join links (ADR-0021). The chair generates one and shares it
     * wherever the organization already talks — usually a WhatsApp group —
     * and holding a valid link *is* the chair's authorization, so accepting
     * joins immediately rather than queuing another approval.
     *
     * Every invite is therefore revocable and expiring: those two columns are
     * what keeps a link that escapes the intended group from being a
     * permanent open door.
     */
    public function up(): void
    {
        Schema::create('organization_invites', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            // Unguessable and unique — this token is the whole credential.
            $table->string('token', 64)->unique();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at');
            // null = no limit. Useful for "one link for the whole group".
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses')->default(0);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invites');
    }
};
