<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * OrganizationRole went from six roles (OWNER/ADMIN/TREASURER/COMMITTEE/
 * MEMBER/RESIDENT) to the four mobile-design roles that back the five UI
 * roles (KETUA/BENDAHARA/SEKRETARIS/ANGGOTA — PANITIA is derived per-event
 * from event_committees, never a stored org-level role). Literal strings
 * on purpose: this must still be correct after OrganizationRole no longer
 * has the old cases.
 */
return new class extends Migration
{
    private const MAP = [
        'OWNER' => 'KETUA',
        'ADMIN' => 'ANGGOTA',
        'TREASURER' => 'BENDAHARA',
        'COMMITTEE' => 'ANGGOTA',
        'MEMBER' => 'ANGGOTA',
        'RESIDENT' => 'ANGGOTA',
    ];

    public function up(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table('organization_memberships')->where('role', $old)->update(['role' => $new]);
        }
    }

    public function down(): void
    {
        // Six roles collapsed into four; ADMIN/COMMITTEE/RESIDENT can't be
        // recovered distinctly from ANGGOTA. Not reversible.
    }
};
