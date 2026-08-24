<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\Organization;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->roleIn($announcement->organization) !== null;
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->isOrganizerOf($organization);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->isOrganizerOf($announcement->organization);
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->isOrganizerOf($announcement->organization);
    }
}
