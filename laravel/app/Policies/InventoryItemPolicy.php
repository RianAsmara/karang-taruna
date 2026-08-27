<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\User;

class InventoryItemPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    public function view(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->roleIn($inventoryItem->organization) !== null;
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->isPengurusOf($organization);
    }

    public function update(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->isPengurusOf($inventoryItem->organization);
    }

    public function delete(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->isPengurusOf($inventoryItem->organization);
    }

    /**
     * Any member may borrow — availability, not role, is the gate.
     */
    public function borrow(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->roleIn($inventoryItem->organization) !== null;
    }
}
