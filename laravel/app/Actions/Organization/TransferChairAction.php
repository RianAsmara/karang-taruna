<?php

namespace App\Actions\Organization;

use App\Enums\OrganizationRole;
use App\Models\OrganizationMembership;
use Illuminate\Support\Facades\DB;

/**
 * The chair role is unique and can only move, never be granted a
 * second time or left empty — mobile-screens.md § 13/14: "Peran ketua
 * hanya bisa dipindahkan, bukan dihapus." The outgoing chair drops to
 * ANGGOTA; the docs don't specify a choice of landing role for them; a
 * dedicated "demote yourself to X" step isn't in the design, so this
 * is a deliberate, documented default rather than an invented UI flow.
 */
class TransferChairAction
{
    public function handle(OrganizationMembership $currentChair, OrganizationMembership $newChair): void
    {
        DB::transaction(function () use ($currentChair, $newChair) {
            $currentChair->update(['role' => OrganizationRole::Anggota]);
            $newChair->update(['role' => OrganizationRole::Ketua]);
        });
    }
}
