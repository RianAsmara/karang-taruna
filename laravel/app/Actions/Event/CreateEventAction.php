<?php

namespace App\Actions\Event;

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateEventAction
{
    /**
     * Create an event and automatically add its creator to the committee,
     * so the organizer roster always includes whoever set the event up.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Organization $organization, User $creator, array $data): Event
    {
        return DB::transaction(function () use ($organization, $creator, $data) {
            $event = $organization->events()->create([
                ...$data,
                'created_by' => $creator->id,
            ]);

            $membership = $creator->membershipIn($organization);

            if ($membership !== null) {
                $event->committees()->create([
                    'membership_id' => $membership->id,
                ]);
            }

            return $event;
        });
    }
}
