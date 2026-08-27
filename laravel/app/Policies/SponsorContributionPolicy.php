<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\SponsorContribution;
use App\Models\User;

/**
 * "Hanya bendahara dan ketua yang mengelola sponsor" (screen 24) —
 * isTreasurerOf (KETUA/BENDAHARA), not the broader isPengurusOf used by
 * Inventory/Documents.
 */
class SponsorContributionPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    /**
     * Amount, type, status, and event are transparency (any member);
     * contact details are gated separately by the controller/resource,
     * matching the screen 24 permission note.
     */
    public function view(User $user, SponsorContribution $sponsorContribution): bool
    {
        return $user->roleIn($sponsorContribution->organization) !== null;
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->isTreasurerOf($organization);
    }

    public function update(User $user, SponsorContribution $sponsorContribution): bool
    {
        return $user->isTreasurerOf($sponsorContribution->organization);
    }

    public function viewContact(User $user, SponsorContribution $sponsorContribution): bool
    {
        return $user->isTreasurerOf($sponsorContribution->organization);
    }
}
