<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Models\Vote;

class VotePolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    /**
     * "Who may create a vote" was an explicitly open product decision
     * (mobile-ux.md § Open decisions) — resolved to pengurus, matching
     * every other "who creates organizational content" gate already in
     * this app (Events, Sponsors, Inventory).
     */
    public function create(User $user, Organization $organization): bool
    {
        return $user->isPengurusOf($organization);
    }

    /**
     * Every member sees the question, even when ineligible to vote — the
     * eligibility gate is on `respond`, not `view` (screen 27: an
     * ineligible viewer still sees the question plus a PermissionNote).
     */
    public function view(User $user, Vote $vote): bool
    {
        return $user->roleIn($vote->organization) !== null;
    }

    public function respond(User $user, Vote $vote): bool
    {
        return $vote->isEligible($user) && $vote->isOpen();
    }

    /**
     * The voter breakdown ("Siapa memilih apa") never exists for an
     * anonymous vote, for any viewer, including the chair — screen 28.
     */
    public function viewBreakdown(User $user, Vote $vote): bool
    {
        if ($vote->anonymous) {
            return false;
        }

        return $user->isPengurusOf($vote->organization);
    }
}
