<?php

namespace App\Policies;

use App\Models\FinancialAccount;
use App\Models\Organization;
use App\Models\User;

class FinancialAccountPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    public function view(User $user, FinancialAccount $financialAccount): bool
    {
        return $user->roleIn($financialAccount->organization) !== null;
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->isTreasurerOf($organization);
    }

    public function update(User $user, FinancialAccount $financialAccount): bool
    {
        return $user->isTreasurerOf($financialAccount->organization);
    }

    public function delete(User $user, FinancialAccount $financialAccount): bool
    {
        if ($financialAccount->transactions()->exists()) {
            return false;
        }

        return $user->isTreasurerOf($financialAccount->organization);
    }
}
