<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;

class OrganizationMembershipPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    public function view(User $user, OrganizationMembership $membership): bool
    {
        return $user->roleIn($membership->organization) !== null;
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->isOrganizerOf($organization);
    }

    public function update(User $user, OrganizationMembership $membership): bool
    {
        return $user->isOrganizerOf($membership->organization);
    }

    public function delete(User $user, OrganizationMembership $membership): bool
    {
        if ($membership->role === OrganizationRole::Owner) {
            return false;
        }

        return $user->isOrganizerOf($membership->organization);
    }
}
