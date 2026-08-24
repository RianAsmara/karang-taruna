<?php

namespace App\Policies;

use App\Models\FinancialCategory;
use App\Models\Organization;
use App\Models\User;

class FinancialCategoryPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    public function view(User $user, FinancialCategory $financialCategory): bool
    {
        return $user->roleIn($financialCategory->organization) !== null;
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->isTreasurerOf($organization);
    }

    public function update(User $user, FinancialCategory $financialCategory): bool
    {
        return $user->isTreasurerOf($financialCategory->organization);
    }

    public function delete(User $user, FinancialCategory $financialCategory): bool
    {
        if ($financialCategory->transactions()->exists()) {
            return false;
        }

        return $user->isTreasurerOf($financialCategory->organization);
    }
}
