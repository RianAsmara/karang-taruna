<?php

namespace App\Policies;

use App\Models\InventoryLoan;
use App\Models\User;

class InventoryLoanPolicy
{
    /**
     * The borrower returns it themselves, or any pengurus does on their
     * behalf (e.g. someone else finding the item back in the gudang).
     */
    public function return(User $user, InventoryLoan $inventoryLoan): bool
    {
        if ($user->isPengurusOf($inventoryLoan->organization)) {
            return true;
        }

        $membership = $user->membershipIn($inventoryLoan->organization);

        return $membership !== null && $inventoryLoan->borrower_membership_id === $membership->id;
    }
}
