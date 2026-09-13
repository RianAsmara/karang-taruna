<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\User;

/**
 * Inviting is managing membership, so it follows the same gate the member
 * list already uses rather than inventing a parallel rule.
 */
class OrganizationInvitePolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->isChairOf($organization);
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->isChairOf($organization);
    }

    public function revoke(User $user, OrganizationInvite $invite): bool
    {
        return $user->isChairOf($invite->organization);
    }
}
