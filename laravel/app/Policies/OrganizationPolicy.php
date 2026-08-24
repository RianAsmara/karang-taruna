<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Any authenticated user may create a new organization; they become its OWNER.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Any active member may view their organization.
     */
    public function view(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    public function manageOrganization(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) === OrganizationRole::Owner;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->manageOrganization($user, $organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) === OrganizationRole::Owner;
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $user->isOrganizerOf($organization);
    }
}
