<?php

namespace App\Policies;

use App\Models\MemberDue;
use App\Models\Organization;
use App\Models\User;

class MemberDuePolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    /**
     * The treasury team sees every due; a member may see their own.
     */
    public function view(User $user, MemberDue $memberDue): bool
    {
        if ($user->isTreasurerOf($memberDue->organization)) {
            return true;
        }

        $membership = $user->membershipIn($memberDue->organization);

        return $membership !== null && $memberDue->membership_id === $membership->id;
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->isTreasurerOf($organization);
    }

    public function update(User $user, MemberDue $memberDue): bool
    {
        return $user->isTreasurerOf($memberDue->organization);
    }

    public function delete(User $user, MemberDue $memberDue): bool
    {
        if ($memberDue->payments()->exists()) {
            return false;
        }

        return $user->isTreasurerOf($memberDue->organization);
    }

    public function recordPayment(User $user, MemberDue $memberDue): bool
    {
        return $user->isTreasurerOf($memberDue->organization);
    }
}
