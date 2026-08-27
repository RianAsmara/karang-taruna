<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    public function view(User $user, Event $event): bool
    {
        return $user->roleIn($event->organization) !== null;
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->isPengurusOf($organization);
    }

    public function update(User $user, Event $event): bool
    {
        return $event->isManagedBy($user);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->isChairOf($event->organization);
    }
}
