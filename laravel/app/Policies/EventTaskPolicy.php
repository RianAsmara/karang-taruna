<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventTask;
use App\Models\User;

class EventTaskPolicy
{
    public function view(User $user, EventTask $eventTask): bool
    {
        return $user->roleIn($eventTask->event->organization) !== null;
    }

    public function create(User $user, Event $event): bool
    {
        return $event->isManagedBy($user);
    }

    public function update(User $user, EventTask $eventTask): bool
    {
        return $eventTask->event->isManagedBy($user);
    }

    /**
     * The assignee may update their own task's status without being able
     * to edit its title, description, priority, or reassign it.
     */
    public function updateStatus(User $user, EventTask $eventTask): bool
    {
        if ($eventTask->event->isManagedBy($user)) {
            return true;
        }

        $membership = $user->membershipIn($eventTask->event->organization);

        return $membership !== null && $eventTask->assignee_membership_id === $membership->id;
    }

    public function delete(User $user, EventTask $eventTask): bool
    {
        return $eventTask->event->isManagedBy($user);
    }
}
