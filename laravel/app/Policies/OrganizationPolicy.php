<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * A user with no organization may create their first one — signup
     * depends on it (a freshly registered user holds no membership, so a
     * blanket chair-only rule would strand them, and the mobile no-org
     * gate in `(app)/_layout.tsx` would loop). Once they belong to an
     * organization, creating *another* is KETUA-only: an ordinary member
     * shouldn't be able to spin up organizations while inside one.
     * They become KETUA of whatever they create.
     */
    public function create(User $user): bool
    {
        if ($user->memberships()->doesntExist()) {
            return true;
        }

        return $user->memberships()->where('role', OrganizationRole::Ketua)->exists();
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
