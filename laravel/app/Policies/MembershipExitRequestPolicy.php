<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\MembershipExitRequest;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;

class MembershipExitRequestPolicy
{
    /**
     * A member may ask to leave their own organization. The chair may
     * not: an organization is never left chairless, so the role must be
     * handed over first (same rule that blocks removing a chair).
     */
    public function create(User $user, OrganizationMembership $membership): bool
    {
        return $user->id === $membership->user_id
            && $membership->role !== OrganizationRole::Ketua;
    }

    /**
     * Only the chair decides — this is the whole point of the flow.
     */
    public function decide(User $user, MembershipExitRequest $exitRequest): bool
    {
        return $user->isChairOf($exitRequest->organization);
    }

    /**
     * The chair sees the organization's pending queue.
     */
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->isChairOf($organization);
    }
}
