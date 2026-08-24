<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;

class EventParticipantPolicy
{
    /**
     * Any organization member may register themselves for an event.
     */
    public function create(User $user, Event $event): bool
    {
        return $user->roleIn($event->organization) !== null;
    }

    /**
     * The organizer may remove any participant; a member may cancel their
     * own registration.
     */
    public function delete(User $user, EventParticipant $eventParticipant): bool
    {
        if ($eventParticipant->event->isManagedBy($user)) {
            return true;
        }

        $membership = $user->membershipIn($eventParticipant->event->organization);

        return $membership !== null && $eventParticipant->membership_id === $membership->id;
    }
}
