<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Any authenticated user may create a new organization; they become its KETUA.
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
        return $user->roleIn($organization) === OrganizationRole::Ketua;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->manageOrganization($user, $organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) === OrganizationRole::Ketua;
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $user->isChairOf($organization);
    }

    /**
     * Ketua-only, and never a superadmin — even one who independently
     * also holds a real ketua membership somewhere. Superadmin is
     * platform-level and strictly read-only (docs/decisions.md ADR-0018:
     * "no write endpoints exist... cannot mutate any organization's
     * data"); theming is a write, so it must stay unreachable regardless
     * of any other role the same user happens to hold.
     */
    public function manageTheme(User $user, Organization $organization): bool
    {
        return $this->manageOrganization($user, $organization) && ! $user->is_superadmin;
    }
}
