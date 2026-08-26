<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventParticipantStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationController extends Controller
{
    public function current(Organization $organization, OrganizationMembership $membership): JsonResource
    {
        $this->authorize('view', $organization);

        return (new OrganizationResource($organization))->additional([
            'membership' => [
                'id' => $membership->id,
                'role' => $membership->role->value,
                'roleLabel' => $membership->role->label(),
                'participatedEventsCount' => EventParticipant::query()
                    ->where('membership_id', $membership->id)
                    ->where('status', EventParticipantStatus::Registered)
                    ->whereHas('event', fn ($query) => $query->where('status', EventStatus::Completed))
                    ->count(),
            ],
        ]);
    }
}
