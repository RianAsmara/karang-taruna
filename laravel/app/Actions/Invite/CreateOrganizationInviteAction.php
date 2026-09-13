<?php

namespace App\Actions\Invite;

use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\User;
use Illuminate\Support\Carbon;

class CreateOrganizationInviteAction
{
    /**
     * Default lifetime for a new link. Long enough to survive a weekend of
     * nobody checking the group chat, short enough that a forgotten link
     * closes itself.
     */
    public const DEFAULT_TTL_DAYS = 7;

    public function handle(Organization $organization, User $createdBy, ?int $maxUses = null): OrganizationInvite
    {
        return $organization->invites()->create([
            'token' => OrganizationInvite::generateToken(),
            'created_by' => $createdBy->id,
            'expires_at' => Carbon::now()->addDays(self::DEFAULT_TTL_DAYS),
            'max_uses' => $maxUses,
            // Set explicitly rather than leaning on the column default: the
            // freshly-created model is serialized straight into the API
            // response, and a DB default is not reflected in memory — the
            // client would receive `uses: null` on creation.
            'uses' => 0,
        ]);
    }
}
